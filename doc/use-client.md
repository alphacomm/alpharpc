# Using the Client

Using the AlphaRPC client is quite simple. It consists of three steps:

 * Configure the Client.
 * Perform a request.
 * (Optional:) Fetch the response.

Also see check the [example client](https://github.com/alphacomm/alpharpc/blob/master/examples/client-reverse.php).

## Configure the Client

You start with configuring the Client:

```php
use AlphaRPC\Client\Client;

$client = new Client();

// Tell it where to find the Client Handler
$client->addManager('tcp://127.0.0.1:61002');
```

## Perform a request

Then, you have to perform a request. You have two choices here:

 * A synchronous request
 * An asynchronous request

### Synchronous request

In case of a synchronous request, you do:

```php
$params = array('Hello World!');
$response = $client->request('reverse', $params);
```

Now, you will have the response in the `$response` variable.

### Asynchronous request

In case of an asynchronous request, you need two steps:

```php
$params = array('Hello World!');

// Start the request.
$request = $client->startRequest('reverse', $params);

// Do something else.
sleep(1);

// Fetch the response (and wait for it if it is not yet available).
$waitForIt = true;
$response = $client->fetchResponse($request, $waitForIt);
```

Now, again you will have the response in the `$response` variable again.

### What about that `$waitForIt` variable?

Now, you may have noticed that we included a `$waitForIt` parameter when fetching the response. With this flag, you can indicate whether you want to wait until the response is available.

If you set this parameters to `false` and the response is not yet available, you will receive a `AlphaRPC\Common\TimeoutException`.

You can safely try to fetch the response again.

NOTE: fetching a response is **not necessary**! If you just want to send a request to a Worker, without caring about the result (in the client), you may choose not to fetch the response. That effectively makes your request a background taks.
