<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Mink;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * Provides UI helper methods using the HTTP kernel to make requests.
 *
 * This is for use by Kernel tests, with the following limitations:
 * - There is no logged in user. Use \Drupal\Tests\user\Traits\UserCreationTrait
 *   to set a current user.
 * - There is no active theme. To place blocks, a test must first install a
 *   theme and then set it as active.
 * - Session semantics differ from normal page requests. Do not rely on session
 *   state beyond its existence (no persistence or regeneration).
 * - Page caching modules will not work. See \Drupal\Tests\Traits\Core\Cache\PageCachePolicyTrait
 *   for how to set them up.
 */
trait HttpKernelUiHelperTrait {

  use BrowserHtmlDebugTrait;

  /**
   * Mink session manager.
   *
   * This is lazily initialized by the first call to self::drupalGet().
   *
   * @var \Behat\Mink\Mink|null
   */
  protected ?Mink $mink;

  /**
   * Retrieves a Drupal path.
   *
   * Requests are sent to the HTTP kernel.
   *
   * @param \Drupal\Core\Url|string $path
   *   The Drupal path to load into Mink controlled browser, as a string or a
   *   Url object. (Note that the Symfony browser's functionality of paths
   *   relative to the previous request is not available, because an initial '/'
   *   is assumed if not present.)
   * @param array $options
   *   (optional) Options to be forwarded to the URL generator. The 'absolute'
   *   option is not supported.
   * @param string[] $headers
   *   An array containing additional HTTP request headers, the array keys are
   *   the header names and the array values the header values. This is useful
   *   to set for example the "Accept-Language" header for requesting the page
   *   in a different language. Note that Mink's BrowserKitDriver normalizes
   *   header names to uppercase, while Symfony's HTTP classes normalize to
   *   lower case, which may cause a header to not be found with certain APIs.
   *
   * @return string
   *   The retrieved HTML string.
   *
   * @see \Drupal\Tests\BrowserTestBase::getHttpClient()
   */
  protected function drupalGet($path, array $options = [], array $headers = []): string {
    $session = $this->getSession();

    if (is_string($path) && !str_starts_with($path, '/')) {
      $path = '/' . $path;
    }

    if (is_object($path) || $options) {
      $path = $this->buildUrl($path, $options);
    }

    foreach ($headers as $header_name => $header_value) {
      assert(is_string($header_name));

      $session->setRequestHeader($header_name, $header_value);
    }

    $session->visit($path);

    $out = $session->getPage()->getContent();

    if ($this->htmlOutputEnabled) {
      $html_output = 'GET request to: ' . $path;
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }

    return $out;
  }

  /**
   * Follows a link by complete name.
   *
   * Will click the first link found with this link text unless $index is
   * specified.
   *
   * If the link is not found, an assertion will fail, halting the test.
   *
   * @param string|\Stringable $label
   *   Text between the anchor tags.
   * @param int $index
   *   (optional) The index number for cases where multiple links have the same
   *   text. Defaults to 0.
   */
  protected function clickLink(string|\Stringable $label, int $index = 0): void {
    $label = (string) $label;
    $links = $this->getSession()->getPage()->findAll('named', ['link', $label]);
    $this->assertArrayHasKey($index, $links, 'The link ' . $label . ' was not found on the page.');

    // Use static::drupalGet() rather than the click() method on the element,
    // because that will not produce HTML debug output.
    $this->drupalGet($links[$index]->getAttribute('href'));
  }

  /**
   * Builds a URL from a system path or a URL object.
   *
   * @param string|\Drupal\Core\Url $path
   *   A system path or a URL object.
   * @param array $options
   *   Options to be passed to Url::fromUri(). If $path is a Url object, any
   *   options it has will take priority over this parameter.
   *
   * @return string
   *   A URL string.
   */
  protected function buildUrl(Url|string $path, array $options = []): string {
    assert(empty($options['absolute']), 'Absolute URLs are not usable with drupalGet().');

    if ($path instanceof Url) {
      if ($options) {
        $url_options = $path->getOptions();
        $options = $url_options + $options;
        $path->setOptions($options);
      }
      return $path->toString();
    }

    $uri = $path === '<front>' ? 'base:/' : 'base:/' . $path;
    return Url::fromUri($uri, $options)->toString();
  }

  /**
   * Returns Mink session.
   *
   * The lazily initializes Mink the first time it is called.
   *
   * @param string $name
   *   (optional) Name of the session. Defaults to the active session.
   *
   * @return \Behat\Mink\Session
   *   The active Mink session object.
   */
  public function getSession($name = NULL): Session {
    // Lazily initialize the Mink session. We do this because unlike Browser
    // tests where there should definitely be requests made, this is not
    // necessarily the case with Kernel tests.
    if (!isset($this->mink)) {
      $this->initMink();
      // Set up the browser test output file.
      $this->initBrowserOutputFile();
    }

    return $this->mink->getSession($name);
  }

  /**
   * Initializes Mink sessions.
   *
   * Helper for static::getSession().
   */
  protected function initMink(): void {
    $driver = $this->getDefaultDriverInstance();

    $selectors_handler = new SelectorsHandler([
      'hidden_field_selector' => new HiddenFieldSelector(),
    ]);
    $session = new Session($driver, $selectors_handler);
    $this->mink = new Mink();
    $this->mink->registerSession('default', $session);
    $this->mink->setDefaultSessionName('default');
  }

  /**
   * Gets an instance of the default Mink driver.
   *
   * @return \Behat\Mink\Driver\DriverInterface
   *   Instance of default Mink driver.
   *
   * @throws \InvalidArgumentException
   *   When provided default Mink driver class can't be instantiated.
   */
  protected function getDefaultDriverInstance(): DriverInterface {
    $http_kernel = $this->container->get('http_kernel');
    $browserkit_client = new HttpKernelBrowser($http_kernel);
    $driver = new BrowserKitDriver($browserkit_client);
    return $driver;
  }

  /**
   * Returns WebAssert object.
   *
   * @param string $name
   *   (optional) Name of the session. Defaults to the active session.
   *
   * @return \Drupal\Tests\WebAssert
   *   A new web-assert option for asserting the presence of elements with.
   */
  public function assertSession($name = NULL): WebAssert {
    $this->addToAssertionCount(1);
    return new WebAssert($this->getSession($name));
  }

}
