# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.0 - 2016-02-11

### Added

- [#21](https://github.com/zendframework/zend-feed/pull/21) edits, revises, and
  prepares the documentation for publication at https://zendframework.github.io/zend-feed/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#20](https://github.com/zendframework/zend-feed/pull/20) makes the two
  zend-servicemanager extension manager implementations forwards compatible
  with version 3, and the overall code base forwards compatible with zend-stdlib
  v3.

## 2.6.0 - 2015-11-24

### Added

- [#13](https://github.com/zendframework/zend-feed/pull/13) introduces
  `Zend\Feed\Writer\StandaloneExtensionManager`, an implementation of
  `Zend\Feed\Writer\ExtensionManagerInterface` that has no dependencies.
  `Zend\Feed\Writer\ExtensionManager` now composes this by default, instead of
  `Zend\Feed\Writer\ExtensionPluginManager`, for managing the various feed and
  entry extensions. If you relied on `ExtensionPluginManager` previously, you
  will need to create an instance manually and inject it into the `Writer`
  instance.
- [#14](https://github.com/zendframework/zend-feed/pull/14) introduces:
  - `Zend\Feed\Reader\Http\HeaderAwareClientInterface`, which extends
    `ClientInterface` and adds an optional argument to the `get()` method,
    `array $headers = []`; this argument allows specifying request headers for
    the client to send. `$headers` should have header names for keys, and the
    values should be arrays of strings/numbers representing the header values
    (if only a single value is necessary, it should be represented as an single
    value array).
  - `Zend\Feed\Reader\Http\HeaderAwareResponseInterface`, which extends
    `ResponseInterface` and adds the method `getHeader($name, $default = null)`.
    Clients may return either a `ResponseInterface` or
    `HeaderAwareResponseInterface` instance.
  - `Zend\Feed\Reader\Http\Response`, which is an implementation of
    `HeaderAwareResponseInterface`. Its constructor accepts the status code,
    body, and, optionally, headers.
  - `Zend\Feed\Reader\Http\Psr7ResponseDecorator`, which is an implementation of
    `HeaderAwareResponseInterface`. Its constructor accepts a PSR-7 response
    instance, and the various methdos then proxy to those methods. This should
    make creating wrappers for PSR-7 HTTP clients trivial.
  - `Zend\Feed\Reader\Http\ZendHttpClientDecorator`, which decorates a
    `Zend\Http\Client` instance, implements `HeaderAwareClientInterface`, and
    returns a `Response` instance seeded from the zend-http response upon
    calling `get()`. The class exposes a `getDecoratedClient()` method to allow
    retrieval of the decorated zend-http client instance.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#5](https://github.com/zendframework/zend-feed/pull/5) fixes the enclosure
  length check to allow zero and integer strings.
- [#2](https://github.com/zendframework/zend-feed/pull/2) ensures that the
  routine for "absolutising" a link in `Reader\FeedSet` always generates a URI
  with a scheme.
- [#14](https://github.com/zendframework/zend-feed/pull/14) makes the following
  changes to fix behavior around HTTP clients used within
  `Zend\Feed\Reader\Reader`:
  - `setHttpClient()` now ensures that the passed client is either a
    `Zend\Feed\Reader\Http\ClientInterface` or `Zend\Http\Client`, raising an
    `InvalidArgumentException` if neither. If a `Zend\Http\Client` is passed, it
    is passed to the constructor of `Zend\Feed\Reader\Http\ZendHttpClientDecorator`,
    and the decorator instance is used.
  - `getHttpClient()` now *always* returns a `Zend\Feed\Reader\Http\ClientInterface`
    instance. If no instance is currently registered, it lazy loads a
    `ZendHttpClientDecorator` instance.
  - `import()` was updated to consume a `ClientInterface` instance; when caches
    are in play, it checks the client against `HeaderAwareClientInterface` to
    determine if it can check for HTTP caching headers, and, if so, to retrieve
    them.
  - `findFeedLinks()` was updated to consume a `ClientInterface`.
