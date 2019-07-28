<?php

namespace Drupal\Tests\jsonapi\Functional;

use Behat\Mink\Driver\BrowserKitDriver;
use Drupal\Core\Url;
use GuzzleHttp\RequestOptions;

/**
 * Boilerplate for JSON:API Functional tests' HTTP requests.
 *
 * @internal
 */
trait JsonApiRequestTestTrait {

  /**
   * Performs a HTTP request. Wraps the Guzzle HTTP client.
   *
   * Why wrap the Guzzle HTTP client? Because we want to keep the actual test
   * code as simple as possible, and hence not require them to specify the
   * 'http_errors = FALSE' request option, nor do we want them to have to
   * convert Drupal Url objects to strings.
   *
   * We also don't want to follow redirects automatically, to ensure these tests
   * are able to detect when redirects are added or removed.
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function request($method, Url $url, array $request_options) {
    $this->refreshVariables();
    $request_options[RequestOptions::HTTP_ERRORS] = FALSE;
    $request_options[RequestOptions::ALLOW_REDIRECTS] = FALSE;
    $request_options = $this->decorateWithXdebugCookie($request_options);
    $client = $this->getSession()->getDriver()->getClient()->getClient();
    return $client->request($method, $url->setAbsolute(TRUE)->toString(), $request_options);
  }

  /**
   * Adds the Xdebug cookie to the request options.
   *
   * @param array $request_options
   *   The request options.
   *
   * @return array
   *   Request options updated with the Xdebug cookie if present.
   */
  protected function decorateWithXdebugCookie(array $request_options) {
    $session = $this->getSession();
    $driver = $session->getDriver();
    if ($driver instanceof BrowserKitDriver) {
      $client = $driver->getClient();
      foreach ($client->getCookieJar()->all() as $cookie) {
        if (isset($request_options[RequestOptions::HEADERS]['Cookie'])) {
          $request_options[RequestOptions::HEADERS]['Cookie'] .= '; ' . $cookie->getName() . '=' . $cookie->getValue();
        }
        else {
          $request_options[RequestOptions::HEADERS]['Cookie'] = $cookie->getName() . '=' . $cookie->getValue();
        }
      }
    }
    return $request_options;
  }

}
