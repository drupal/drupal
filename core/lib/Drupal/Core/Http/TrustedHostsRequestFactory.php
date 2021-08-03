<?php

namespace Drupal\Core\Http;

use Symfony\Component\HttpFoundation\InputBag as SymfonyInputBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a request factory for requests using host verification.
 *
 * Because the trusted host patterns for requests are stored statically, they
 * are consulted even for fake request created with Request::create(), whose
 * host is 'localhost' by default. Such requests would fail host verification
 * unless 'localhost' matches one of the trusted host patterns. To circumvent
 * this problem, this factory injects the server variables from the main request
 * into each newly created request, so that the host is correctly set even for
 * fake requests and they properly pass host verification.
 *
 * @see \Drupal\Core\DrupalKernel::setupTrustedHosts()
 */
class TrustedHostsRequestFactory {

  /**
   * The host of the main request.
   *
   * @var string
   */
  protected $host;

  /**
   * Creates a new TrustedHostsRequestFactory.
   *
   * @param string $host
   *   The host of the main request.
   */
  public function __construct($host) {
    $this->host = (string) $host;
  }

  /**
   * Creates a new request object.
   *
   * @param array $query
   *   (optional) The query (GET) or request (POST) parameters.
   * @param array $request
   *   (optional) An array of request variables.
   * @param array $attributes
   *   (optional) An array of attributes.
   * @param array $cookies
   *   (optional) The request cookies ($_COOKIE).
   * @param array $files
   *   (optional) The request files ($_FILES).
   * @param array $server
   *   (optional) The server parameters ($_SERVER).
   * @param string $content
   *   (optional) The raw body data.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A new request object.
   */
  public function createRequest(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = NULL) {
    if (empty($server['HTTP_HOST']) || ($server['HTTP_HOST'] === 'localhost' && $this->host !== 'localhost')) {
      $server['HTTP_HOST'] = $this->host;
    }
    $request = new Request($query, $request, $attributes, $cookies, $files, $server, $content);

    // Replace ParameterBag with InputBag for compatibility with Symfony 5.
    // @todo Remove this when Symfony 4 is no longer supported.
    foreach (['request', 'query', 'cookies'] as $bag) {
      if (!($bag instanceof SymfonyInputBag)) {
        $request->$bag = new InputBag($request->$bag->all());
      }
    }

    return $request;
  }

}
