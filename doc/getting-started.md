# Getting Started

AlphaRPC is a library for performing remote procedure calls from PHP.
It uses ZeroMQ to communicate with the different parts.

## Installing ZeroMQ and php-zmq

To get AlphaRPC working, you first need to install libzmq (the ZeroMQ library) and php-zmq (the PHP extension).
`screen` is used to start all the [handlers](documentation#handlers) at once.

### On Debian Wheezy

```bash
sudo apt-get install libzmq-dev php-pear php5-dev screen
sudo pear channel-discover pear.zero.mq
sudo pecl install pear.zero.mq/zmq-beta
sudo /bin/sh -c 'echo extension=zmq.so > /etc/php5/conf.d/zmq.ini'
```

### On Ubuntu 14.04

```bash
sudo apt-get install libzmq3-dev libzmq3 php5-dev php-pear pkg-config
sudo pecl install zmq-beta
sudo /bin/sh -c 'echo extension=zmq.so > /etc/php5/mods-available/zmq.ini'
sudo php5enmod zmq
```

## Install Composer

AlphaRPC uses [composer](https://getcomposer.org/) for its dependency management.

## Installing AlphaRPC and Composer

Installing AlphaRPC is easy: just clone the repository and run composer.

```bash
git clone https://github.com/alphacomm/alpharpc.git
cd alpharpc
curl -sS https://getcomposer.org/installer | php
./composer.phar install
```

## Starting AlphaRPC

For now, we start AlphaRPC in some screen sessions. In production, we recommend you to use something like [supervisor](http://supervisord.org/).

```bash
bin/start-handlers
```

## First request

If all went well, AlphaRPC is running now. To perform the first request, run:

```bash
examples/client-reverse.php
```

To provide your own text to be reversed, add it to the command:

```bash
examples/client-reverse.php 'hello world'
```

## Done!

Now you have a working AlphaRPC installation. You are ready to perform some common tasks:

 * [Use the AlphaRPC Client in your project](use-client.md)
 * [Create a worker](create-worker.md)
