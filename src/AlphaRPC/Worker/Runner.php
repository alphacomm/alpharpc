<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Worker
 */

namespace AlphaRPC\Worker;

use AlphaRPC\Common\PidTracker;
use AlphaRPC\Common\Socket\Factory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Worker
 */
class Runner implements LoggerAwareInterface
{
    /**
     * Whether this Worker is still supposed to run.
     *
     * @var boolean
     */
    protected $running = false;

    /**
     *
     * @var Worker
     */
    protected $worker = null;

    /**
     *
     * @var string
     */
    protected $workerHandlerAddress = null;

    /**
     *
     * @var string
     */
    protected $serviceAddress = null;

    /**
     *
     * @var PidTracker
     */
    protected $service = null;

    /**
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     *
     * @var Factory
     */
    protected $socketFactory = null;

    /**
     * The directory where the IPC files are stored.
     *
     * @var string
     */
    protected $ipcDir;

    public function __construct($workerHandlerAddress, $ipcDir)
    {
        $this->setWorkerHandlerAddress($workerHandlerAddress);
        $this->setIpcDir($ipcDir);
        $this->createServiceAddress();
    }

    /**
     * Creates a string containing the path for the ipc file based on pid.
     *
     * @return \AlphaRPC\Worker\Runner
     */
    protected function createServiceAddress()
    {
        $this->serviceAddress = 'ipc://'.$this->getIpcDir().'/worker-'.getmypid().'.ipc';

        return $this;
    }

    /**
     * Return the path where the service is connecting to.
     *
     * @return string
     */
    public function getServiceAddress()
    {
        return $this->serviceAddress;
    }

    /**
     * Start the Service.
     *
     * This method fork()s and starts a new Service process.
     *
     * The Service process is the process that actually performs
     * the work needed to satisfy a Request.
     *
     * @param callable $bootstrap
     *
     * @throws \RuntimeException
     */
    public function forkAndRunService($bootstrap)
    {
        if (!is_callable($bootstrap)) {
            throw new \RuntimeException('$bootstrap must be callable.');
        }

        if ($this->service !== null) {
            // We already have a service.
            throw new \RuntimeException('Service already started.');
        }

        $serviceAddress = $this->getServiceAddress();
        $factory = $this->getSocketFactory();
        $logger = $this->getLogger();
        try {
            $this->service = PidTracker::fork(function() use ($bootstrap, $serviceAddress, $factory, $logger) {
                $socket = $factory->createReply(Factory::MODE_CONNECT, $serviceAddress);
                $service = new Service($socket);
                $service->setLogger($logger);
                call_user_func($bootstrap, $service);
                $service->run();
            });
            $this->getLogger()->info('Service (PID: '.$this->service->getPid().') started.');
        } catch (\RuntimeException $ex) {
            $this->getLogger()->error($ex->getMessage());
            throw $ex;
        }

        return $this;
    }

    /**
     * Whether this Worker is still running.
     *
     * @return boolean
     */
    public function isRunning()
    {
        return $this->running;
    }

    /**
     * Run the worker.
     */
    public function run()
    {
        pcntl_signal_dispatch();
        if ($this->service == null || !$this->service->isAlive()) {
            throw new \RuntimeException('Service not running.');
        }

        $workerHandlerSocket = $this->socketFactory->createRequest(Factory::MODE_CONNECT, $this->workerHandlerAddress);
        $serviceSocket = $this->socketFactory->createRequest(Factory::MODE_BIND, $this->serviceAddress);

        $this->worker = new Worker();
        $this->worker->setLogger($this->getLogger());

        $comm = new WorkerCommunication($this->worker, $workerHandlerSocket, $serviceSocket);
        $comm->setLogger($this->getLogger());

        $this->registerSignalHandler();
        $this->running = true;

        $comm->start();
        while (true) {
            $comm->process();
            pcntl_signal_dispatch();

            if (!$this->service->isAlive()) {
                $this->worker->onServiceDown();
            }

            if ($this->worker->getState() == Worker::INVALID) {
                $this->stop();
                break;
            }
        }

        // Kill the service.
        $this->service->killAndWait();
        $this->worker->onServiceDown();

        $this->getLogger()->debug('Run completed with state: '.$this->worker->getState());
    }

    /**
     * Stops this Worker.
     *
     * @param integer $signal [OPTIONAL] Contains the signal in the case of a PCNTL signal.
     */
    public function stop($signal = null)
    {
        if (!$this->running) {
            return;
        }

        if (null !== $signal) {
            $this->getLogger()->info('Stop called because of signal #'.$signal.'. Current state: '.$this->worker->getState().'.');
        } else {
            $this->getLogger()->info('Stop called: '.$this->worker->getState().'.');
        }

        $this->running = false;
        $this->worker->shutdown();
    }

    /**
     * Register the stop function to the signal handler.
     *
     * @return null
     */
    protected function registerSignalHandler()
    {
        pcntl_signal(SIGINT, array($this, 'stop'));
        pcntl_signal(SIGTERM, array($this, 'stop'));
    }

    /**
     * Set the logger.
     *
     * The logger should be a callable that accepts 2 parameters, a
     * string $message and an integer $priority.
     *
     * @param LoggerInterface $logger
     *
     * @return AlphaRPC
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = new \Psr\Log\NullLogger();
        }

        return $this->logger;
    }

    /**
     * Get the path where the ipc files are stored.
     *
     * @return string
     */
    public function getIpcDir()
    {
        return $this->ipcDir;
    }

    /**
     *
     * @param string $ipcDir
     *
     * @return \AlphaRPC\Worker\ZMWorker
     * @throws \RuntimeException
     */
    public function setIpcDir($ipcDir)
    {
        if (!file_exists($ipcDir) || !is_dir($ipcDir)) {
            throw new \RuntimeException('IpcDir '.$ipcDir.' is not a directory.');
        }
        $this->ipcDir = $ipcDir;

        return $this;
    }

    /**
     * Get the address where the workerhandler is listening.
     *
     * @return string
     */
    public function getWorkerHandlerAddress()
    {
        return $this->workerHandlerAddress;
    }

    /**
     * Set the address where the workerhandler is listening.
     *
     * @param string $workerHandlerAddress
     *
     * @return \AlphaRPC\Worker\ZMWorker
     */
    public function setWorkerHandlerAddress($workerHandlerAddress)
    {
        $this->workerHandlerAddress = $workerHandlerAddress;

        return $this;
    }

    /**
     * Returns the socket factory.
     *
     * @return \AlphaRPC\Common\Socket\Factory
     */
    public function getSocketFactory()
    {
        if ($this->socketFactory === null) {
            $this->socketFactory = new Factory();
        }

        return $this->socketFactory;
    }

    /**
     * Sets the socket factory.
     *
     * @param \AlphaRPC\Common\Socket\Factory $socketFactory
     *
     * @return \AlphaRPC\Worker\ZMWorker
     */
    public function setSocketFactory(Factory $socketFactory)
    {
        $this->socketFactory = $socketFactory;

        return $this;
    }
}
