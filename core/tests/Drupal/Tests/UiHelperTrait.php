<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Behat\Mink\Driver\BrowserKitDriver;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Test\RefreshVariablesTrait;
use Drupal\Core\Url;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Provides UI helper methods.
 */
trait UiHelperTrait {

  use BrowserHtmlDebugTrait;
  use RefreshVariablesTrait;

  /**
   * The current user logged in using the Mink controlled browser.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $loggedInUser = FALSE;

  /**
   * Use one-time login links instead of submitting the login form.
   */
  protected bool $useOneTimeLoginLinks = TRUE;

  /**
   * The number of meta refresh redirects to follow, or NULL if unlimited.
   *
   * @var null|int
   */
  protected $maximumMetaRefreshCount = NULL;

  /**
   * The number of meta refresh redirects followed during ::drupalGet().
   *
   * @var int
   */
  protected $metaRefreshCount = 0;

  /**
   * Fills and submits a form.
   *
   * @param array $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated.
   *
   *   A checkbox can be set to TRUE to be checked and should be set to FALSE to
   *   be unchecked.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated. For example,
   *   'Save'. The processing of the request depends on this value. For example,
   *   a form may have one button with the value 'Save' and another button with
   *   the value 'Delete', and execute different code depending on which one is
   *   clicked.
   * @param string $form_html_id
   *   (optional) HTML ID of the form to be submitted. On some pages
   *   there are many identical forms, so just using the value of the submit
   *   button is not enough. For example: 'trigger-node-presave-assign-form'.
   *   Note that this is not the Drupal $form_id, but rather the HTML ID of the
   *   form, which is typically the same thing but with hyphens replacing the
   *   underscores.
   */
  protected function submitForm(array $edit, $submit, $form_html_id = NULL) {
    $assert_session = $this->assertSession();

    // Get the form.
    if (isset($form_html_id)) {
      $form = $assert_session->elementExists('xpath', "//form[@id='$form_html_id']");
      $submit_button = $assert_session->buttonExists($submit, $form);
      $action = $form->getAttribute('action');
    }
    else {
      $submit_button = $assert_session->buttonExists($submit);
      $form = $assert_session->elementExists('xpath', './ancestor::form', $submit_button);
      $action = $form->getAttribute('action');
    }

    // Edit the form values.
    foreach ($edit as $name => $value) {
      $field = $assert_session->fieldExists($name, $form);

      $value = match ($field->getAttribute('type')) {
        // Provide support for the values '1' and '0' for checkboxes instead of
        // TRUE and FALSE.
        // @todo Get rid of supporting 1/0 by converting all tests cases using
        // this to boolean values.
        'checkbox' => (bool) $value,
        // Mink only allows strings for text, number and radio button values.
        'text', 'number', 'radio' => (string) $value,
        default => $value,
      };

      $field->setValue($value);
    }

    // Submit form.
    $this->prepareRequest();
    $submit_button->press();

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Check if there are any meta refresh redirects (like Batch API pages).
    if ($this->checkForMetaRefresh()) {
      // We are finished with all meta refresh redirects, so reset the counter.
      $this->metaRefreshCount = 0;
    }

    // Log only for WebDriverTestBase tests because for tests using
    // DrupalTestBrowser we log with ::getResponseLogHandler.
    if ($this->htmlOutputEnabled && !$this->isTestUsingGuzzleClient()) {
      $out = $this->getSession()->getPage()->getContent();
      $html_output = 'POST request to: ' . $action .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }

  }

  /**
   * Logs in a user using the Mink controlled browser.
   *
   * If a user is already logged in, then the current user is logged out before
   * logging in the specified user.
   *
   * Note that neither the current user nor the passed-in user object is
   * populated with data of the logged in user. If you need full access to the
   * user object after logging in, it must be updated manually. If you also need
   * access to the plain-text password of the user (set by drupalCreateUser()),
   * e.g. to log in the same user again, then it must be re-assigned manually.
   * For example:
   * @code
   *   // Create a user.
   *   $account = $this->drupalCreateUser([]);
   *   $this->drupalLogin($account);
   *   // Load real user object.
   *   $pass_raw = $account->passRaw;
   *   $account = User::load($account->id());
   *   $account->passRaw = $pass_raw;
   * @endcode
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object representing the user to log in.
   *
   * @see drupalCreateUser()
   */
  protected function drupalLogin(AccountInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    if ($this->useOneTimeLoginLinks) {
      // Reload to get latest login timestamp.
      $storage = \Drupal::entityTypeManager()->getStorage('user');
      /** @var \Drupal\user\UserInterface $accountUnchanged */
      $accountUnchanged = $storage->loadUnchanged($account->id());
      $login = user_pass_reset_url($accountUnchanged) . '/login?destination=user/' . $account->id();
      $this->drupalGet($login);
    }
    else {
      $this->drupalGet(Url::fromRoute('user.login'));
      $this->submitForm([
        'name' => $account->getAccountName(),
        'pass' => $account->passRaw,
      ], 'Log in');
    }

    // @see ::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), "User {$account->getAccountName()} successfully logged in.");

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Logs a user out of the Mink controlled browser and confirms.
   *
   * Confirms logout by checking the login page.
   */
  protected function drupalLogout() {
    // Make a request to the logout page, and redirect to the user page, the
    // idea being if you were properly logged out you should be seeing a login
    // screen.
    $assert_session = $this->assertSession();
    $destination = Url::fromRoute('user.page')->toString();
    $this->drupalGet(Url::fromRoute('user.logout.confirm', options: ['query' => ['destination' => $destination]]));
    // Target the submit button using the name rather than the value to work
    // regardless of the user interface language.
    $this->submitForm([], 'op', 'user-logout-confirm');
    $assert_session->fieldExists('name');
    $assert_session->fieldExists('pass');

    $this->drupalResetSession();
  }

  /**
   * Resets the current active session back to Anonymous session.
   */
  protected function drupalResetSession(): void {
    // @see BrowserTestBase::drupalUserIsLoggedIn()
    unset($this->loggedInUser->sessionId);
    $this->loggedInUser = FALSE;
    \Drupal::currentUser()->setAccount(new AnonymousUserSession());
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
  public function assertSession($name = NULL) {
    $this->addToAssertionCount(1);
    return new WebAssert($this->getSession($name), $this->baseUrl);
  }

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * @param string|\Drupal\Core\Url $path
   *   Drupal path or URL to load into Mink controlled browser.
   * @param array $options
   *   (optional) Options to be forwarded to the URL generator.
   * @param string[] $headers
   *   An array containing additional HTTP request headers, the array keys are
   *   the header names and the array values the header values. This is useful
   *   to set for example the "Accept-Language" header for requesting the page
   *   in a different language. Note that not all headers are supported, for
   *   example the "Accept" header is always overridden by the browser. For
   *   testing REST APIs it is recommended to obtain a separate HTTP client
   *   using getHttpClient() and performing requests that way.
   *
   * @return string
   *   The retrieved HTML string, also available as $this->getRawContent()
   *
   * @see \Drupal\Tests\BrowserTestBase::getHttpClient()
   */
  protected function drupalGet($path, array $options = [], array $headers = []) {
    $options['absolute'] = TRUE;
    $url = $this->buildUrl($path, $options);

    $session = $this->getSession();

    $this->prepareRequest();
    foreach ($headers as $header_name => $header_value) {
      if (is_int($header_name)) {
        @trigger_error('Passing an integer as header name to ' . __METHOD__ . '() is deprecated in drupal:11.1.0 and will be removed from drupal:12.0.0. Update the calling code to pass the header name as a key. See https://www.drupal.org/node/3456178', E_USER_DEPRECATED);
        [$header_name, $header_value] = explode(':', $header_value);
      }
      if (is_null($header_value)) {
        @trigger_error('Using null as a header value to ' . __METHOD__ . '() is deprecated in drupal:11.1.0 and will be removed from drupal:12.0.0. Use an empty string instead. See https://www.drupal.org/node/3456233', E_USER_DEPRECATED);
        $header_value = '';
      }
      $session->setRequestHeader($header_name, $header_value);
    }

    $session->visit($url);
    $out = $session->getPage()->getContent();

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Replace original page output with new output from redirected page(s).
    if ($new = $this->checkForMetaRefresh()) {
      $out = $new;
      // We are finished with all meta refresh redirects, so reset the counter.
      $this->metaRefreshCount = 0;
    }

    // Log only for WebDriverTestBase tests because for BrowserKitDriver we log
    // with ::getResponseLogHandler.
    if ($this->htmlOutputEnabled && !$this->isTestUsingGuzzleClient()) {
      $html_output = 'GET request to: ' . $url .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }

    return $out;
  }

  /**
   * Builds an absolute URL from a system path or a URL object.
   *
   * @param string|\Drupal\Core\Url $path
   *   A system path or a URL object.
   * @param array $options
   *   Options to be passed to Url::fromUri().
   *
   * @return string
   *   An absolute URL string.
   */
  protected function buildUrl($path, array $options = []) {
    global $base_path;

    if ($path instanceof Url) {
      $url_options = $path->getOptions();
      $options = $url_options + $options;
      $path->setOptions($options);
      return $path->setAbsolute()->toString();
    }
    // The URL generator service is not necessarily available yet; e.g., in
    // interactive installer tests.
    elseif (\Drupal::hasService('url_generator')) {
      // Strip $base_path, if existent.
      $length = strlen($base_path);
      if (substr($path, 0, $length) === $base_path) {
        $path = substr($path, $length);
      }
      // Additionally strip any forward slashes.
      if (strlen($path) > 1) {
        $path = ltrim($path, '/');
      }

      $force_internal = isset($options['external']) && $options['external'] == FALSE;
      if (!$force_internal && UrlHelper::isExternal($path)) {
        return Url::fromUri($path, $options)->toString();
      }
      else {
        $uri = $path === '<front>' ? 'base:/' : 'base:/' . $path;
        // Path processing is needed for language prefixing.  Skip it when a
        // path that may look like an external URL is being used as internal.
        $options['path_processing'] = !$force_internal;
        return Url::fromUri($uri, $options)
          ->setAbsolute()
          ->toString();
      }
    }
    else {
      return $this->getAbsoluteUrl($path);
    }
  }

  /**
   * Takes a path and returns an absolute path.
   *
   * @param string $path
   *   A path from the Mink controlled browser content.
   *
   * @return string
   *   The $path with $base_url prepended, if necessary.
   */
  protected function getAbsoluteUrl($path) {
    global $base_url, $base_path;

    $parts = parse_url($path);
    if (empty($parts['host'])) {
      // Ensure that we have a string (and no xpath object).
      $path = (string) $path;
      // Strip $base_path, if existent.
      $length = strlen($base_path);
      if (substr($path, 0, $length) === $base_path) {
        $path = substr($path, $length);
      }
      // Ensure that we have an absolute path.
      if (empty($path) || $path[0] !== '/') {
        $path = '/' . $path;
      }
      // Finally, prepend the $base_url.
      $path = $base_url . $path;
    }
    return $path;
  }

  /**
   * Prepare for a request to testing site.
   *
   * The testing site is protected via a SIMPLETEST_USER_AGENT cookie that is
   * checked by drupal_valid_test_ua().
   *
   * @see drupal_valid_test_ua()
   */
  protected function prepareRequest() {
    $session = $this->getSession();
    $session->setCookie('SIMPLETEST_USER_AGENT', drupal_generate_test_ua($this->databasePrefix));
  }

  /**
   * Returns whether a given user account is logged in.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object to check.
   *
   * @return bool
   *   Return TRUE if the user is logged in, FALSE otherwise.
   */
  protected function drupalUserIsLoggedIn(AccountInterface $account): bool {
    $logged_in = FALSE;

    if (isset($account->sessionId)) {
      $session_handler = \Drupal::service('session_handler.storage');
      $logged_in = (bool) $session_handler->read($account->sessionId);
    }

    return $logged_in;
  }

  /**
   * Clicks the element with the given CSS selector.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to click.
   */
  protected function click($css_selector) {
    $starting_url = $this->getSession()->getCurrentUrl();
    $this->getSession()->getDriver()->click($this->cssSelectToXpath($css_selector));
    // Log only for WebDriverTestBase tests because for BrowserKitDriver we log
    // with ::getResponseLogHandler.
    if ($this->htmlOutputEnabled && !$this->isTestUsingGuzzleClient()) {
      $out = $this->getSession()->getPage()->getContent();
      $html_output =
        'Clicked element with CSS selector: ' . $css_selector .
        '<hr />Starting URL: ' . $starting_url .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }
  }

  /**
   * Follows a link by complete name.
   *
   * Will click the first link found with this link text.
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   * @param int $index
   *   (optional) The index number for cases where multiple links have the same
   *   text. Defaults to 0.
   */
  protected function clickLink($label, $index = 0) {
    $label = (string) $label;
    $links = $this->getSession()->getPage()->findAll('named', ['link', $label]);
    $this->assertArrayHasKey($index, $links, 'The link ' . $label . ' was not found on the page.');
    $links[$index]->click();
  }

  /**
   * Retrieves the plain-text content from the current page.
   */
  protected function getTextContent() {
    return $this->getSession()->getPage()->getText();
  }

  /**
   * Get the current URL from the browser.
   *
   * @return string
   *   The current URL.
   */
  protected function getUrl() {
    return $this->getSession()->getCurrentUrl();
  }

  /**
   * Checks for meta refresh tag and if found call drupalGet() recursively.
   *
   * This function looks for the http-equiv attribute to be set to "Refresh" and
   * is case-insensitive.
   *
   * @return string|false
   *   Either the new page content or FALSE.
   */
  protected function checkForMetaRefresh() {
    $refresh = $this->cssSelect('meta[http-equiv="Refresh"], meta[http-equiv="refresh"]');
    if (!empty($refresh) && (!isset($this->maximumMetaRefreshCount) || $this->metaRefreshCount < $this->maximumMetaRefreshCount)) {
      // Parse the content attribute of the meta tag for the format:
      // "[delay]: URL=[page_to_redirect_to]".
      if (preg_match('/\d+;\s*URL=\'?(?<url>[^\']*)/i', $refresh[0]->getAttribute('content'), $match)) {
        $this->metaRefreshCount++;
        return $this->drupalGet($this->getAbsoluteUrl(Html::decodeEntities($match['url'])));
      }
    }
    return FALSE;
  }

  /**
   * Searches elements using a CSS selector in the raw content.
   *
   * The search is relative to the root element (HTML tag normally) of the page.
   *
   * @param string $selector
   *   CSS selector to use in the search.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The list of elements on the page that match the selector.
   */
  protected function cssSelect($selector) {
    return $this->getSession()->getPage()->findAll('css', $selector);
  }

  /**
   * Translates a CSS expression to its XPath equivalent.
   *
   * The search is relative to the root element (HTML tag normally) of the page.
   *
   * @param string $selector
   *   CSS selector to use in the search.
   * @param bool $html
   *   (optional) Enables HTML support. Disable it for XML documents.
   * @param string $prefix
   *   (optional) The prefix for the XPath expression.
   *
   * @return string
   *   The equivalent XPath of a CSS expression.
   */
  protected function cssSelectToXpath($selector, $html = TRUE, $prefix = 'descendant-or-self::'): string {
    return (new CssSelectorConverter($html))->toXPath($selector, $prefix);
  }

  /**
   * Determines if test is using DrupalTestBrowser.
   *
   * @return bool
   *   TRUE if test is using DrupalTestBrowser.
   */
  protected function isTestUsingGuzzleClient(): bool {
    $driver = $this->getSession()->getDriver();
    if ($driver instanceof BrowserKitDriver) {
      return $driver->getClient() instanceof DrupalTestBrowser;
    }
    return FALSE;
  }

}
