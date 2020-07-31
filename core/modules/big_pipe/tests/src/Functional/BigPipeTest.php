<?php

namespace Drupal\Tests\big_pipe\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;
use Drupal\big_pipe\Render\BigPipe;
use Drupal\big_pipe_test\BigPipePlaceholderTestCases;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests BigPipe's no-JS detection & response delivery (with and without JS).
 *
 * Covers:
 * - big_pipe_page_attachments()
 * - \Drupal\big_pipe\Controller\BigPipeController
 * - \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber
 * - \Drupal\big_pipe\Render\BigPipe
 *
 * @group big_pipe
 */
class BigPipeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['big_pipe', 'big_pipe_test', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected $dumpHeaders = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Ignore the <meta> refresh that big_pipe.module sets. It causes a redirect
    // to a page that sets another cookie, which causes WebTestBase to lose the
    // session cookie. To avoid this problem, tests should first call
    // drupalGet() and then call checkForMetaRefresh() manually, and then reset
    // $this->maximumMetaRefreshCount and $this->metaRefreshCount.
    // @see doMetaRefresh()
    $this->maximumMetaRefreshCount = 0;
  }

  /**
   * Performs a single <meta> refresh explicitly.
   *
   * This test disables the automatic <meta> refresh checking, each time it is
   * desired that this runs, a test case must explicitly call this.
   *
   * @see setUp()
   */
  protected function performMetaRefresh() {
    $this->maximumMetaRefreshCount = 1;
    $this->checkForMetaRefresh();
    $this->maximumMetaRefreshCount = 0;
    $this->metaRefreshCount = 0;
  }

  /**
   * Tests BigPipe's no-JS detection.
   *
   * Covers:
   * - big_pipe_page_attachments()
   * - \Drupal\big_pipe\Controller\BigPipeController
   */
  public function testNoJsDetection() {
    $no_js_to_js_markup = '<script>document.cookie = "' . BigPipeStrategy::NOJS_COOKIE . '=1; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT"</script>';

    // 1. No session (anonymous).
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSessionCookieExists(FALSE);
    $this->assertBigPipeNoJsCookieExists(FALSE);
    $this->assertNoRaw('<noscript><meta http-equiv="Refresh" content="0; URL=');
    $this->assertNoRaw($no_js_to_js_markup);

    // 2. Session (authenticated).
    $this->drupalLogin($this->rootUser);
    $this->assertSessionCookieExists(TRUE);
    $this->assertBigPipeNoJsCookieExists(FALSE);
    $this->assertRaw('<noscript><meta http-equiv="Refresh" content="0; URL=' . base_path() . 'big_pipe/no-js?destination=' . base_path() . 'user/1" />' . "\n" . '</noscript>');
    $this->assertNoRaw($no_js_to_js_markup);
    $this->assertBigPipeNoJsMetaRefreshRedirect();
    $this->assertBigPipeNoJsCookieExists(TRUE);
    $this->assertNoRaw('<noscript><meta http-equiv="Refresh" content="0; URL=');
    $this->assertRaw($no_js_to_js_markup);
    $this->drupalLogout();

    // Close the prior connection and remove the collected state.
    $this->getSession()->reset();

    // 3. Session (anonymous).
    $this->drupalGet(Url::fromRoute('user.login', [], ['query' => ['trigger_session' => 1]]));
    $this->drupalGet(Url::fromRoute('user.login'));
    $this->assertSessionCookieExists(TRUE);
    $this->assertBigPipeNoJsCookieExists(FALSE);
    $this->assertRaw('<noscript><meta http-equiv="Refresh" content="0; URL=' . base_path() . 'big_pipe/no-js?destination=' . base_path() . 'user/login" />' . "\n" . '</noscript>');
    $this->assertNoRaw($no_js_to_js_markup);
    $this->assertBigPipeNoJsMetaRefreshRedirect();
    $this->assertBigPipeNoJsCookieExists(TRUE);
    $this->assertNoRaw('<noscript><meta http-equiv="Refresh" content="0; URL=');
    $this->assertRaw($no_js_to_js_markup);

    // Close the prior connection and remove the collected state.
    $this->getSession()->reset();

    // Edge case: route with '_no_big_pipe' option.
    $this->drupalGet(Url::fromRoute('no_big_pipe'));
    $this->assertSessionCookieExists(FALSE);
    $this->assertBigPipeNoJsCookieExists(FALSE);
    $this->assertNoRaw('<noscript><meta http-equiv="Refresh" content="0; URL=');
    $this->assertNoRaw($no_js_to_js_markup);
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(Url::fromRoute('no_big_pipe'));
    $this->assertSessionCookieExists(TRUE);
    $this->assertBigPipeNoJsCookieExists(FALSE);
    $this->assertNoRaw('<noscript><meta http-equiv="Refresh" content="0; URL=');
    $this->assertNoRaw($no_js_to_js_markup);
  }

  /**
   * Tests BigPipe-delivered HTML responses when JavaScript is enabled.
   *
   * Covers:
   * - \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber
   * - \Drupal\big_pipe\Render\BigPipe
   * - \Drupal\big_pipe\Render\BigPipe::sendPlaceholders()
   *
   * @see \Drupal\big_pipe_test\BigPipePlaceholderTestCases
   */
  public function testBigPipe() {
    // Simulate production.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_HIDE)->save();

    $this->drupalLogin($this->rootUser);
    $this->assertSessionCookieExists(TRUE);
    $this->assertBigPipeNoJsCookieExists(FALSE);

    $connection = Database::getConnection();
    $log_count = $connection->query('SELECT COUNT(*) FROM {watchdog}')->fetchField();

    // By not calling performMetaRefresh() here, we simulate JavaScript being
    // enabled, because as far as the BigPipe module is concerned, JavaScript is
    // enabled in the browser as long as the BigPipe no-JS cookie is *not* set.
    // @see setUp()
    // @see performMetaRefresh()

    $this->drupalGet(Url::fromRoute('big_pipe_test'));
    $this->assertBigPipeResponseHeadersPresent();
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'cache_tag_set_in_lazy_builder');

    $this->setCsrfTokenSeedInTestEnvironment();
    $cases = $this->getTestCases();
    $this->assertBigPipeNoJsPlaceholders([
      $cases['edge_case__invalid_html']->bigPipeNoJsPlaceholder     => $cases['edge_case__invalid_html']->embeddedHtmlResponse,
      $cases['html_attribute_value']->bigPipeNoJsPlaceholder        => $cases['html_attribute_value']->embeddedHtmlResponse,
      $cases['html_attribute_value_subset']->bigPipeNoJsPlaceholder => $cases['html_attribute_value_subset']->embeddedHtmlResponse,
    ]);
    $this->assertBigPipePlaceholders([
      $cases['html']->bigPipePlaceholderId                             => Json::encode($cases['html']->embeddedAjaxResponseCommands),
      $cases['edge_case__html_non_lazy_builder']->bigPipePlaceholderId => Json::encode($cases['edge_case__html_non_lazy_builder']->embeddedAjaxResponseCommands),
      $cases['exception__lazy_builder']->bigPipePlaceholderId          => NULL,
      $cases['exception__embedded_response']->bigPipePlaceholderId     => NULL,
    ], [
      0 => $cases['edge_case__html_non_lazy_builder']->bigPipePlaceholderId,
      // The 'html' case contains the 'status messages' placeholder, which is
      // always rendered last.
      1 => $cases['html']->bigPipePlaceholderId,
    ]);

    $this->assertRaw('</body>', 'Closing body tag present.');

    // Verifying BigPipe assets are present.
    $this->assertFalse(empty($this->getDrupalSettings()), 'drupalSettings present.');
    $this->assertContains('big_pipe/big_pipe', explode(',', $this->getDrupalSettings()['ajaxPageState']['libraries']), 'BigPipe asset library is present.');

    // Verify that the two expected exceptions are logged as errors.
    $this->assertEqual($log_count + 2, $connection->query('SELECT COUNT(*) FROM {watchdog}')->fetchField(), 'Two new watchdog entries.');
    // Using the method queryRange() allows contrib database drivers the ability
    // to insert their own limit and offset functionality.
    $records = $connection->queryRange('SELECT * FROM {watchdog} ORDER BY wid DESC', 0, 2)->fetchAll();
    $this->assertEqual(RfcLogLevel::ERROR, $records[0]->severity);
    $this->assertStringContainsString('Oh noes!', (string) unserialize($records[0]->variables)['@message']);
    $this->assertEqual(RfcLogLevel::ERROR, $records[1]->severity);
    $this->assertStringContainsString('You are not allowed to say llamas are not cool!', (string) unserialize($records[1]->variables)['@message']);

    // Verify that 4xx responses work fine. (4xx responses are handled by
    // subrequests to a route pointing to a controller with the desired output.)
    $this->drupalGet(Url::fromUri('base:non-existing-path'));

    // Simulate development.
    // Verifying BigPipe provides useful error output when an error occurs
    // while rendering a placeholder if verbose error logging is enabled.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet(Url::fromRoute('big_pipe_test'));
    // The 'edge_case__html_exception' case throws an exception.
    $this->assertRaw('The website encountered an unexpected error. Please try again later');
    $this->assertRaw('You are not allowed to say llamas are not cool!');
    $this->assertNoRaw(BigPipe::STOP_SIGNAL, 'BigPipe stop signal absent: error occurred before then.');
    $this->assertNoRaw('</body>', 'Closing body tag absent: error occurred before then.');
    // The exception is expected. Do not interpret it as a test failure.
    unlink($this->root . '/' . $this->siteDirectory . '/error.log');
  }

  /**
   * Tests BigPipe-delivered HTML responses when JavaScript is disabled.
   *
   * Covers:
   * - \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber
   * - \Drupal\big_pipe\Render\BigPipe
   * - \Drupal\big_pipe\Render\BigPipe::sendNoJsPlaceholders()
   *
   * @see \Drupal\big_pipe_test\BigPipePlaceholderTestCases
   */
  public function testBigPipeNoJs() {
    // Simulate production.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_HIDE)->save();

    $this->drupalLogin($this->rootUser);
    $this->assertSessionCookieExists(TRUE);
    $this->assertBigPipeNoJsCookieExists(FALSE);

    // By calling performMetaRefresh() here, we simulate JavaScript being
    // disabled, because as far as the BigPipe module is concerned, it is
    // enabled in the browser when the BigPipe no-JS cookie is set.
    // @see setUp()
    // @see performMetaRefresh()
    $this->performMetaRefresh();
    $this->assertBigPipeNoJsCookieExists(TRUE);

    $this->drupalGet(Url::fromRoute('big_pipe_test'));
    $this->assertBigPipeResponseHeadersPresent();
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'cache_tag_set_in_lazy_builder');

    $this->setCsrfTokenSeedInTestEnvironment();
    $cases = $this->getTestCases();
    $this->assertBigPipeNoJsPlaceholders([
      $cases['edge_case__invalid_html']->bigPipeNoJsPlaceholder           => $cases['edge_case__invalid_html']->embeddedHtmlResponse,
      $cases['html_attribute_value']->bigPipeNoJsPlaceholder              => $cases['html_attribute_value']->embeddedHtmlResponse,
      $cases['html_attribute_value_subset']->bigPipeNoJsPlaceholder       => $cases['html_attribute_value_subset']->embeddedHtmlResponse,
      $cases['html']->bigPipeNoJsPlaceholder                              => $cases['html']->embeddedHtmlResponse,
      $cases['edge_case__html_non_lazy_builder']->bigPipeNoJsPlaceholder  => $cases['edge_case__html_non_lazy_builder']->embeddedHtmlResponse,
      $cases['exception__lazy_builder']->bigPipePlaceholderId             => NULL,
      $cases['exception__embedded_response']->bigPipePlaceholderId        => NULL,
    ]);

    // Verifying there are no BigPipe placeholders & replacements.
    $this->assertEqual('<none>', $this->drupalGetHeader('BigPipe-Test-Placeholders'));
    // Verifying BigPipe start/stop signals are absent.
    $this->assertNoRaw(BigPipe::START_SIGNAL, 'BigPipe start signal absent.');
    $this->assertNoRaw(BigPipe::STOP_SIGNAL, 'BigPipe stop signal absent.');

    // Verifying BigPipe assets are absent.
    $this->assertTrue(!isset($this->getDrupalSettings()['bigPipePlaceholderIds']) && empty($this->getDrupalSettings()['ajaxPageState']), 'BigPipe drupalSettings and BigPipe asset library absent.');
    $this->assertRaw('</body>', 'Closing body tag present.');

    // Verify that 4xx responses work fine. (4xx responses are handled by
    // subrequests to a route pointing to a controller with the desired output.)
    $this->drupalGet(Url::fromUri('base:non-existing-path'));

    // Simulate development.
    // Verifying BigPipe provides useful error output when an error occurs
    // while rendering a placeholder if verbose error logging is enabled.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet(Url::fromRoute('big_pipe_test'));
    // The 'edge_case__html_exception' case throws an exception.
    $this->assertRaw('The website encountered an unexpected error. Please try again later');
    $this->assertRaw('You are not allowed to say llamas are not cool!');
    $this->assertNoRaw('</body>', 'Closing body tag absent: error occurred before then.');
    // The exception is expected. Do not interpret it as a test failure.
    unlink($this->root . '/' . $this->siteDirectory . '/error.log');
  }

  /**
   * Tests BigPipe with a multi-occurrence placeholder.
   */
  public function testBigPipeMultiOccurrencePlaceholders() {
    $this->drupalLogin($this->rootUser);
    $this->assertSessionCookieExists(TRUE);
    $this->assertBigPipeNoJsCookieExists(FALSE);

    // By not calling performMetaRefresh() here, we simulate JavaScript being
    // enabled, because as far as the BigPipe module is concerned, JavaScript is
    // enabled in the browser as long as the BigPipe no-JS cookie is *not* set.
    // @see setUp()
    // @see performMetaRefresh()

    $this->drupalGet(Url::fromRoute('big_pipe_test_multi_occurrence'));
    $big_pipe_placeholder_id = 'callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&amp;args%5B0%5D&amp;token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA';
    $expected_placeholder_replacement = '<script type="application/vnd.drupal-ajax" data-big-pipe-replacement-for-placeholder-with-id="' . $big_pipe_placeholder_id . '">';
    $this->assertRaw('The count is 1.');
    $this->assertNoRaw('The count is 2.');
    $this->assertNoRaw('The count is 3.');
    $raw_content = $this->getSession()->getPage()->getContent();
    $this->assertTrue(substr_count($raw_content, $expected_placeholder_replacement) == 1, 'Only one placeholder replacement was found for the duplicate #lazy_builder arrays.');

    // By calling performMetaRefresh() here, we simulate JavaScript being
    // disabled, because as far as the BigPipe module is concerned, it is
    // enabled in the browser when the BigPipe no-JS cookie is set.
    // @see setUp()
    // @see performMetaRefresh()
    $this->performMetaRefresh();
    $this->assertBigPipeNoJsCookieExists(TRUE);
    $this->drupalGet(Url::fromRoute('big_pipe_test_multi_occurrence'));
    $this->assertRaw('The count is 1.');
    $this->assertNoRaw('The count is 2.');
    $this->assertNoRaw('The count is 3.');
  }

  protected function assertBigPipeResponseHeadersPresent() {
    // Check that Cache-Control header set to "private".
    $this->assertSession()->responseHeaderContains('Cache-Control', 'private');
    $this->assertEqual('no-store, content="BigPipe/1.0"', $this->drupalGetHeader('Surrogate-Control'));
    $this->assertEqual('no', $this->drupalGetHeader('X-Accel-Buffering'));
  }

  /**
   * Asserts expected BigPipe no-JS placeholders are present and replaced.
   *
   * @param array $expected_big_pipe_nojs_placeholders
   *   Keys: BigPipe no-JS placeholder markup. Values: expected replacement
   *   markup.
   */
  protected function assertBigPipeNoJsPlaceholders(array $expected_big_pipe_nojs_placeholders) {
    $this->assertSetsEqual(array_keys($expected_big_pipe_nojs_placeholders), array_map('rawurldecode', explode(' ', $this->drupalGetHeader('BigPipe-Test-No-Js-Placeholders'))));
    foreach ($expected_big_pipe_nojs_placeholders as $big_pipe_nojs_placeholder => $expected_replacement) {
      // Checking whether the replacement for the BigPipe no-JS placeholder
      // $big_pipe_nojs_placeholder is present.
      $this->assertNoRaw($big_pipe_nojs_placeholder);
      if ($expected_replacement !== NULL) {
        $this->assertRaw($expected_replacement);
      }
    }
  }

  /**
   * Asserts expected BigPipe placeholders are present and replaced.
   *
   * @param array $expected_big_pipe_placeholders
   *   Keys: BigPipe placeholder IDs. Values: expected AJAX response.
   * @param array $expected_big_pipe_placeholder_stream_order
   *   Keys: BigPipe placeholder IDs. Values: expected AJAX response. Keys are
   *   defined in the order that they are expected to be rendered & streamed.
   */
  protected function assertBigPipePlaceholders(array $expected_big_pipe_placeholders, array $expected_big_pipe_placeholder_stream_order) {
    $this->assertSetsEqual(array_keys($expected_big_pipe_placeholders), explode(' ', $this->drupalGetHeader('BigPipe-Test-Placeholders')));
    $placeholder_positions = [];
    $placeholder_replacement_positions = [];
    foreach ($expected_big_pipe_placeholders as $big_pipe_placeholder_id => $expected_ajax_response) {
      // Verify expected placeholder.
      $expected_placeholder_html = '<span data-big-pipe-placeholder-id="' . $big_pipe_placeholder_id . '"></span>';
      $this->assertRaw($expected_placeholder_html, 'BigPipe placeholder for placeholder ID "' . $big_pipe_placeholder_id . '" found.');
      $pos = strpos($this->getSession()->getPage()->getContent(), $expected_placeholder_html);
      $placeholder_positions[$pos] = $big_pipe_placeholder_id;
      // Verify expected placeholder replacement.
      $expected_placeholder_replacement = '<script type="application/vnd.drupal-ajax" data-big-pipe-replacement-for-placeholder-with-id="' . $big_pipe_placeholder_id . '">';
      $result = $this->xpath('//script[@data-big-pipe-replacement-for-placeholder-with-id=:id]', [':id' => Html::decodeEntities($big_pipe_placeholder_id)]);
      if ($expected_ajax_response === NULL) {
        $this->assertCount(0, $result);
        $this->assertNoRaw($expected_placeholder_replacement);
        continue;
      }
      $this->assertEqual($expected_ajax_response, trim($result[0]->getText()));
      $this->assertRaw($expected_placeholder_replacement);
      $pos = strpos($this->getSession()->getPage()->getContent(), $expected_placeholder_replacement);
      $placeholder_replacement_positions[$pos] = $big_pipe_placeholder_id;
    }
    ksort($placeholder_positions, SORT_NUMERIC);
    $this->assertEqual(array_keys($expected_big_pipe_placeholders), array_values($placeholder_positions));
    $placeholders = array_map(function (NodeElement $element) {
      return $element->getAttribute('data-big-pipe-placeholder-id');
    }, $this->cssSelect('[data-big-pipe-placeholder-id]'));
    $this->assertEqual(count($expected_big_pipe_placeholders), count(array_unique($placeholders)));
    $expected_big_pipe_placeholders_with_replacements = [];
    foreach ($expected_big_pipe_placeholder_stream_order as $big_pipe_placeholder_id) {
      $expected_big_pipe_placeholders_with_replacements[$big_pipe_placeholder_id] = $expected_big_pipe_placeholders[$big_pipe_placeholder_id];
    }
    $this->assertEqual($expected_big_pipe_placeholders_with_replacements, array_filter($expected_big_pipe_placeholders));
    $this->assertSetsEqual(array_keys($expected_big_pipe_placeholders_with_replacements), array_values($placeholder_replacement_positions));
    $this->assertEqual(count($expected_big_pipe_placeholders_with_replacements), preg_match_all('/' . preg_quote('<script type="application/vnd.drupal-ajax" data-big-pipe-replacement-for-placeholder-with-id="', '/') . '/', $this->getSession()->getPage()->getContent()));

    // Verifying BigPipe start/stop signals.
    $this->assertRaw(BigPipe::START_SIGNAL, 'BigPipe start signal present.');
    $this->assertRaw(BigPipe::STOP_SIGNAL, 'BigPipe stop signal present.');
    $start_signal_position = strpos($this->getSession()->getPage()->getContent(), BigPipe::START_SIGNAL);
    $stop_signal_position = strpos($this->getSession()->getPage()->getContent(), BigPipe::STOP_SIGNAL);
    $this->assertTrue($start_signal_position < $stop_signal_position, 'BigPipe start signal appears before stop signal.');

    // Verifying BigPipe placeholder replacements and start/stop signals were
    // streamed in the correct order.
    $expected_stream_order = array_keys($expected_big_pipe_placeholders_with_replacements);
    array_unshift($expected_stream_order, BigPipe::START_SIGNAL);
    array_push($expected_stream_order, BigPipe::STOP_SIGNAL);
    $actual_stream_order = $placeholder_replacement_positions + [
        $start_signal_position => BigPipe::START_SIGNAL,
        $stop_signal_position => BigPipe::STOP_SIGNAL,
      ];
    ksort($actual_stream_order, SORT_NUMERIC);
    $this->assertEqual($expected_stream_order, array_values($actual_stream_order));
  }

  /**
   * Ensures CSRF tokens can be generated for the current user's session.
   */
  protected function setCsrfTokenSeedInTestEnvironment() {
    $session_data = $this->container->get('session_handler.write_safe')->read($this->getSession()->getCookie($this->getSessionName()));
    $csrf_token_seed = unserialize(explode('_sf2_meta|', $session_data)[1])['s'];
    $this->container->get('session_manager.metadata_bag')->setCsrfTokenSeed($csrf_token_seed);
  }

  /**
   * @return \Drupal\big_pipe_test\BigPipePlaceholderTestCase[]
   */
  protected function getTestCases($has_session = TRUE) {
    return BigPipePlaceholderTestCases::cases($this->container, $this->rootUser);
  }

  /**
   * Asserts whether arrays A and B are equal, when treated as sets.
   */
  protected function assertSetsEqual(array $a, array $b) {
    return count($a) == count($b) && !array_diff_assoc($a, $b);
  }

  /**
   * Asserts whether a BigPipe no-JS cookie exists or not.
   */
  protected function assertBigPipeNoJsCookieExists($expected) {
    $this->assertCookieExists('big_pipe_nojs', $expected, 'BigPipe no-JS');
  }

  /**
   * Asserts whether a session cookie exists or not.
   */
  protected function assertSessionCookieExists($expected) {
    $this->assertCookieExists($this->getSessionName(), $expected, 'Session');
  }

  /**
   * Asserts whether a cookie exists on the client or not.
   */
  protected function assertCookieExists($cookie_name, $expected, $cookie_label) {
    $this->assertEqual($expected, !empty($this->getSession()->getCookie($cookie_name)), $expected ? "$cookie_label cookie exists." : "$cookie_label cookie does not exist.");
  }

  /**
   * Calls ::performMetaRefresh() and asserts the responses.
   */
  protected function assertBigPipeNoJsMetaRefreshRedirect() {
    $original_url = $this->getSession()->getCurrentUrl();

    // Disable automatic following of redirects by the HTTP client, so that this
    // test can analyze the response headers of each redirect response.
    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
    $this->performMetaRefresh();
    $headers[0] = $this->getSession()->getResponseHeaders();
    $statuses[0] = $this->getSession()->getStatusCode();
    $this->performMetaRefresh();
    $headers[1] = $this->getSession()->getResponseHeaders();
    $statuses[1] = $this->getSession()->getStatusCode();
    $this->getSession()->getDriver()->getClient()->followRedirects(TRUE);

    $this->assertEqual($original_url, $this->getSession()->getCurrentUrl(), 'Redirected back to the original location.');

    // First response: redirect.
    $this->assertEqual(302, $statuses[0], 'The first response was a 302 (redirect).');
    $this->assertStringStartsWith('big_pipe_nojs=1', $headers[0]['Set-Cookie'][0], 'The first response sets the big_pipe_nojs cookie.');
    $this->assertEqual($original_url, $headers[0]['Location'][0], 'The first response redirected back to the original page.');
    $this->assertTrue(empty(array_diff(['cookies:big_pipe_nojs', 'session.exists'], explode(' ', $headers[0]['X-Drupal-Cache-Contexts'][0]))), 'The first response varies by the "cookies:big_pipe_nojs" and "session.exists" cache contexts.');
    $this->assertFalse(isset($headers[0]['Surrogate-Control']), 'The first response has no "Surrogate-Control" header.');

    // Second response: redirect followed.
    $this->assertEqual(200, $statuses[1], 'The second response was a 200.');
    $this->assertTrue(empty(array_diff(['cookies:big_pipe_nojs', 'session.exists'], explode(' ', $headers[0]['X-Drupal-Cache-Contexts'][0]))), 'The first response varies by the "cookies:big_pipe_nojs" and "session.exists" cache contexts.');
    $this->assertEqual('no-store, content="BigPipe/1.0"', $headers[1]['Surrogate-Control'][0], 'The second response has a "Surrogate-Control" header.');

    $this->assertNoRaw('<noscript><meta http-equiv="Refresh" content="0; URL=', 'Once the BigPipe no-JS cookie is set, the <meta> refresh is absent: only one redirect ever happens.');
  }

}
