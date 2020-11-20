# HTTP Clients and zend-feed

Several operations in zend-feed's Reader subcomponent require an HTTP client:

- importing a feed
- finding links in a feed

In order to allow developers a choice in HTTP clients, the subcomponent defines
several interfaces and classes. Elsewhere in the documentation, we reference
where an HTTP client may be used; this document details what constitutes an HTTP
client and its behavior, and some of the concrete classes available within the
component for implementing this behavior.

## ClientInterface and HeaderAwareClientInterface

First, we define two interfaces for clients,
`Zend\Feed\Reader\Http\ClientInterface` and `HeaderAwareClientInterface`:

```php
namespace Zend\Feed\Reader\Http;

interface ClientInterface
{
    /**
     * Make a GET request to a given URL.
     *
     * @param string $url
     * @return ResponseInterface
     */
    public function get($url);
}

interface HeaderAwareClientInterface extends ClientInterface
{
    /**
     * Make a GET request to a given URL.
     *
     * @param string $url
     * @param array $headers
     * @return ResponseInterface
     */
    public function get($url, array $headers = []);
}
```

The first is header-agnostic, and assumes that the client will simply perform an
HTTP GET request. The second allows providing headers to the client; typically,
these are used for HTTP caching headers. `$headers` must be in the following
structure:

```php
$headers = [
    'X-Header-Name' => [
        'header',
        'values',
    ],
];
```

i.e., each key is a header name, and each value is an array of values for that
header. If the header represents only a single value, it should be an array with
that value:

```php
$headers = [
    'Accept' => [ 'application/rss+xml' ],
];
```

A call to `get()` should yield a *response*.

## ResponseInterface and HeaderAwareResponseInterface

Responses are modeled using `Zend\Feed\Reader\Http\ResponseInterface` and
`HeaderAwareResponseInterface`:

```php
namespace Zend\Feed\Reader\Http;

class ResponseInterface
{
    /**
     * Retrieve the status code.
     *
     * @return int
     */
    public function getStatusCode();

    /**
     * Retrieve the response body contents.
     *
     * @return string
     */
    public function getBody();
}

class HeaderAwareResponseInterface extends ResponseInterface
{
    /**
     * Retrieve a named header line.
     *
     * Retrieve a header by name; all values MUST be concatenated to a single
     * line. If no matching header is found, return the $default value.
     *
     * @param string $name
     * @param null|string $default
     * @return string
    public function getHeaderLine($name, $default = null);
}
```

Internally, `Reader` will typehint against `ClientInterface` for the bulk of
operations. In some cases, however, certain capabilities are only possible if
the response can provide headers (e.g., for caching); in such cases, it will
check the instance against `HeaderAwareResponseInterface`, and only call
`getHeaderLine()` if it matches.

## Response

zend-feed ships with a generic `ResponseInterface` implementation,
`Zend\Feed\Http\Response`. It implements `HeaderAwareResponseInterface`, and
defines the following constructor:

```php
namespace Zend\Feed\Reader\Http;

class Response implements HeaderAwareResponseInterface
{
    /**
     * Constructor
     *
     * @param int $statusCode Response status code
     * @param string $body Response body
     * @param array $headers Response headers, if available
     */
    public function __construct($statusCode, $body, array $headers = []);
}
```

## PSR-7 Response

[PSR-7](http://www.php-fig.org/psr/psr-7/) defines a set of HTTP message
interfaces, but not a client interface. To facilitate wrapping an HTTP client
that uses PSR-7 messages, we provide `Zend\Feed\Reader\Psr7ResponseDecorator`:

```php
namespace Zend\Feed\Reader\Http;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class Psr7ResponseDecorator implements HeaderAwareResponseInterface
{
    /**
     * @param PsrResponseInterface $response
     */
    public function __construct(PsrResponseInterface $response);

    /**
     * @return PsrResponseInterface
     */
    public function getDecoratedResponse();
}
```

Clients can then take the PSR-7 response they receive, pass it to the decorator,
and return the decorator.

To use the PSR-7 response, you will need to add the PSR-7 interfaces to your
application, if they are not already installed by the client of your choice:

```bash
$ composer require psr/http-message
```

## zend-http

We also provide a zend-http client decorator,
`Zend\Feed\Reader\Http\ZendHttpClientDecorator`:

```php
namespace Zend\Feed\Reader\Http;

use Zend\Http\Client as HttpClient;

class ZendHttpClientDecorator implements HeaderAwareClientInterface
{
    /**
     * @param HttpClient $client
     */
    public function __construct(HttpClient $client);

    /**
     * @return HttpClient
     */
    public function getDecoratedClient();
}
```

Its `get()` implementation returns a `Response` instance seeded from the
zend-http response returned, including status, body, and headers.

zend-http is the default implementation assumed by `Zend\Feed\Reader\Reader`,
but *is not installed by default*. You may install it using composer:

```bash
$ composer require zendframework/zend-http
```

## Providing a client to Reader

By default, `Zend\Feed\Reader\Reader` will lazy load a zend-http client. If you
have not installed zend-http, however, PHP will raise an error indicating the
class is not found!

As such, you have two options:

1. Install zend-http: `composer require zendframework/zend-http`.
2. Inject the `Reader` with your own HTTP client.

To accomplish the second, you will need an implementation of
`Zend\Feed\Reader\Http\ClientInterface` or `HeaderAwareClientInterface`, and an
instance of that implementation. Once you do, you can use the static method
`setHttpClient()` to inject it.

As an example, let's say you've created a PSR-7-based implementation named
`My\Http\Psr7FeedClient`. You could then do the following:

```php
use My\Http\Psr7FeedClient;
use Zend\Feed\Reader\Reader;

Reader::setHttpClient(new Psr7FeedClient());
```

Your client will then be used for all `import()` and `findFeedLinks()`
operations.
