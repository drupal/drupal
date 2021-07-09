<?php

namespace Drupal\Core\File;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default implementation for the file URL generator service.
 */
class FileUrlGenerator implements FileUrlGeneratorInterface {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new file URL generator object.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager, RequestStack $request_stack, ModuleHandlerInterface $module_handler) {
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function generateString(string $uri): string {
    return $this->doGenerateString($uri, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function generateAbsoluteString(string $uri): string {
    return $this->doGenerateString($uri, FALSE);
  }

  /**
   * Creates an absolute web-accessible URL string.
   *
   * @param string $uri
   *   The URI to a file for which we need an external URL, or the path to a
   *   shipped file.
   * @param bool $relative
   *   Whether to return an relative or absolute URL.
   *
   * @return string
   *   An absolute string containing a URL that may be used to access the
   *   file.
   *
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   If a stream wrapper could not be found to generate an external URL.
   */
  protected function doGenerateString(string $uri, bool $relative): string {
    // Allow the URI to be altered, e.g. to serve a file from a CDN or static
    // file server.
    $this->moduleHandler->alter('file_url', $uri);

    $scheme = StreamWrapperManager::getScheme($uri);

    if (!$scheme) {
      $baseUrl = $relative ? base_path() : $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . base_path();
      return $this->generatePath($baseUrl, $uri);
    }
    elseif ($scheme == 'http' || $scheme == 'https' || $scheme == 'data') {
      // Check for HTTP and data URI-encoded URLs so that we don't have to
      // implement getExternalUrl() for the HTTP and data schemes.
      return $relative ? $this->transformRelative($uri) : $uri;
    }
    elseif ($wrapper = $this->streamWrapperManager->getViaUri($uri)) {
      // Attempt to return an external URL using the appropriate wrapper.
      $externalUrl = $wrapper->getExternalUrl();
      return $relative ? $this->transformRelative($externalUrl) : $externalUrl;
    }
    throw new InvalidStreamWrapperException();
  }

  /**
   * Generate a URL path.
   *
   * @param string $base_url
   *   The base URL.
   * @param string $uri
   *   The URI.
   *
   * @return string
   *   The URL path.
   */
  protected function generatePath(string $base_url, string $uri): string {
    // Allow for:
    // - root-relative URIs (e.g. /foo.jpg in http://example.com/foo.jpg)
    // - protocol-relative URIs (e.g. //bar.jpg, which is expanded to
    //   http://example.com/bar.jpg by the browser when viewing a page over
    //   HTTP and to https://example.com/bar.jpg when viewing a HTTPS page)
    // Both types of relative URIs are characterized by a leading slash, hence
    // we can use a single check.
    if (mb_substr($uri, 0, 1) == '/') {
      return $uri;
    }
    else {
      // If this is not a properly formatted stream, then it is a shipped
      // file. Therefore, return the urlencoded URI with the base URL
      // prepended.
      $options = UrlHelper::parse($uri);
      $path = $base_url . UrlHelper::encodePath($options['path']);
      // Append the query.
      if ($options['query']) {
        $path .= '?' . UrlHelper::buildQuery($options['query']);
      }

      // Append fragment.
      if ($options['fragment']) {
        $path .= '#' . $options['fragment'];
      }

      return $path;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generate(string $uri): Url {
    // Allow the URI to be altered, e.g. to serve a file from a CDN or static
    // file server.
    $this->moduleHandler->alter('file_url', $uri);

    $scheme = StreamWrapperManager::getScheme($uri);

    if (!$scheme) {
      // Allow for:
      // - root-relative URIs (e.g. /foo.jpg in http://example.com/foo.jpg)
      // - protocol-relative URIs (e.g. //bar.jpg, which is expanded to
      //   http://example.com/bar.jpg by the browser when viewing a page over
      //   HTTP and to https://example.com/bar.jpg when viewing a HTTPS page)
      // Both types of relative URIs are characterized by a leading slash, hence
      // we can use a single check.
      if (mb_substr($uri, 0, 2) == '//') {
        return Url::fromUri($uri);
      }
      elseif (mb_substr($uri, 0, 1) == '/') {
        return Url::fromUri('base:' . str_replace($this->requestStack->getCurrentRequest()->getBasePath(), '', $uri));
      }
      else {
        // If this is not a properly formatted stream, then it is a shipped
        // file. Therefore, return the urlencoded URI.
        $options = UrlHelper::parse($uri);
        return Url::fromUri('base:' . UrlHelper::encodePath($options['path']), $options);
      }
    }
    elseif ($scheme == 'http' || $scheme == 'https' || $scheme == 'data') {
      // Check for HTTP and data URI-encoded URLs so that we don't have to
      // implement getExternalUrl() for the HTTP and data schemes.
      $options = UrlHelper::parse($uri);
      return Url::fromUri(urldecode($options['path']), $options);
    }
    elseif ($wrapper = $this->streamWrapperManager->getViaUri($uri)) {
      // Attempt to return an external URL using the appropriate wrapper.
      return Url::fromUri('base:' . $this->transformRelative(urldecode($wrapper->getExternalUrl()), FALSE));
    }
    throw new InvalidStreamWrapperException();
  }

  /**
   * {@inheritdoc}
   */
  public function transformRelative(string $file_url, bool $root_relative = TRUE): string {
    // Unfortunately, we pretty much have to duplicate Symfony's
    // Request::getHttpHost() method because Request::getPort() may return NULL
    // instead of a port number.
    $request = $this->requestStack->getCurrentRequest();
    $host = $request->getHost();
    $scheme = $request->getScheme();
    $port = $request->getPort() ?: 80;

    // Files may be accessible on a different port than the web request.
    $file_url_port = parse_url($file_url, PHP_URL_PORT) ?? $port;
    if ($file_url_port != $port) {
      return $file_url;
    }

    if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
      $http_host = $host;
    }
    else {
      $http_host = $host . ':' . $port;
    }

    // If this should not be a root-relative path but relative to the drupal
    // base path, add it to the host to be removed from the URL as well.
    if (!$root_relative) {
      $http_host .= $request->getBasePath();
    }

    return preg_replace('|^https?://' . preg_quote($http_host, '|') . '|', '', $file_url);
  }

}
