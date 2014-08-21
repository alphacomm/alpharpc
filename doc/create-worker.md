# Creating a Worker

## TL;DR

See the [example worker](https://github.com/alphacomm/alpharpc/blob/master/examples/worker-reverse.php).


## Definitions

A worker is the process that runs the actions. It has three components:

* One or more Actions (the functions that you want to execute).
* A Service (which contains the Actions. It runs as a sub-process from the Worker.)
* The Worker (which communicates with the AlphaRPC Worker Handler).

## Create your first worker!

The following code is all there is to it:

```php
use AlphaRPC\Worker\Runner as WorkerRunner;
use AlphaRPC\Worker\Service;

$worker = new WorkerRunner(
    'tcp://127.0.0.1:61002', // Worker Handler address
    './ipc'                  // Writable directory for IPC files.
);

$worker->forkAndRunService(function (Service $service) {
    $service->addAction(
        // Name of the Action.
        'reverse',

        // Implementation of the Action.
        function($param) { return strrev($param); }
    );
});

```

## Run the worker

To run the worker:

```bash
php worker.php
```

## Using the Action

Now you can use the client to perform the Action. See [Using the Client](use-client.md).
