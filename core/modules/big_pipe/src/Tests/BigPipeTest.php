<?php

namespace Drupal\big_pipe\Tests;

use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;
use Drupal\big_pipe\Render\BigPipe;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

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
class BigPipeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['big_pipe', 'big_pipe_test', 'dblog'];

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
    $this->curlClose();
    $this->curlCookies = [];
    $this->cookies = [];

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
    $this->curlClose();
    $this->curlCookies = [];
    $this->cookies = [];

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
   * @see \Drupal\big_pipe\Tests\BigPipePlaceholderTestCases
   */
  public function testBigPipe() {
    // Simulate production.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_HIDE)->save();

    $this->drupalLogin($this->rootUser);
    $this->assertSessionCookieExists(TRUE);
    $this->assertBigPipeNoJsCookieExists(FALSE);

    $log_count = db_query('SELECT COUNT(*) FROM {watchdog}')->fetchField();

    // By not calling performMetaRefresh() here, we simulate JavaScript being
    // enabled, because as far as the BigPipe module is concerned, JavaScript is
    // enabled in the browser as long as the BigPipe no-JS cookie is *not* set.
    // @see setUp()
    // @see performMetaRefresh()

    $this->drupalGet(Url::fromRoute('big_pipe_test'));
    $this->assertBigPipeResponseHeadersPresent();

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
    ]);

    $this->assertRaw('</body>', 'Closing body tag present.');

    $this->pass('Verifying BigPipe assets are present…', 'Debug');
    $this->assertFalse(empty($this->getDrupalSettings()), 'drupalSettings present.');
    $this->assertTrue(in_array('big_pipe/big_pipe', explode(',', $this->getDrupalSettings()['ajaxPageState']['libraries'])), 'BigPipe asset library is present.');

    // Verify that the two expected exceptions are logged as errors.
    $this->assertEqual($log_count + 2, db_query('SELECT COUNT(*) FROM {watchdog}')->fetchField(), 'Two new watchdog entries.');
    $records = db_query('SELECT * FROM {watchdog} ORDER BY wid DESC LIMIT 2')->fetchAll();
    $this->assertEqual(RfcLogLevel::ERROR, $records[0]->severity);
    $this->assertTrue(FALSE !== strpos((string) unserialize($records[0]->variables)['@message'], 'Oh noes!'));
    $this->assertEqual(RfcLogLevel::ERROR, $records[0]->severity);
    $this->assertTrue(FALSE !== strpos((string) unserialize($records[1]->variables)['@message'], 'You are not allowed to say llamas are not cool!'));

    // Verify that 4xx responses work fine. (4xx responses are handled by
    // subrequests to a route pointing to a controller with the desired output.)
    $this->drupalGet(Url::fromUri('base:non-existing-path'));

    // Simulate development.
    $this->pass('Verifying BigPipe provides useful error output when an error occurs while rendering a placeholder if verbose error logging is enabled.', 'Debug');
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet(Url::fromRoute('big_pipe_test'));
    // The 'edge_case__html_exception' case throws an exception.
    $this->assertRaw('The website encountered an unexpected error. Please try again later');
    $this->assertRaw('You are not allowed to say llamas are not cool!');
    $this->assertNoRaw(BigPipe::STOP_SIGNAL, 'BigPipe stop signal absent: error occurred before then.');
    $this->assertNoRaw('</body>', 'Closing body tag absent: error occurred before then.');
    // The exception is expected. Do not interpret it as a test failure.
    unlink(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
  }

  /**
   * Tests BigPipe-delivered HTML responses when JavaScript is disabled.
   *
   * Covers:
   * - \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber
   * - \Drupal\big_pipe\Render\BigPipe
   * - \Drupal\big_pipe\Render\BigPipe::sendNoJsPlaceholders()
   *
   * @see \Drupal\big_pipe\Tests\BigPipePlaceholderTestCases
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

    $this->pass('Verifying there are no BigPipe placeholders & replacements…', 'Debug');
    $this->assertEqual('<none>', $this->drupalGetHeader('BigPipe-Test-Placeholders'));
    $this->pass('Verifying BigPipe start/stop signals are absent…', 'Debug');
    $this->assertNoRaw(BigPipe::START_SIGNAL, 'BigPipe start signal absent.');
    $this->assertNoRaw(BigPipe::STOP_SIGNAL, 'BigPipe stop signal absent.');

    $this->pass('Verifying BigPipe assets are absent…', 'Debug');
    $this->assertFalse(empty($this->getDrupalSettings()), 'drupalSettings and BigPipe asset library absent.');
    $this->assertRaw('</body>', 'Closing body tag present.');

    // Verify that 4xx responses work fine. (4xx responses are handled by
    // subrequests to a route pointing to a controller with the desired output.)
    $this->drupalGet(Url::fromUri('base:non-existing-path'));

    // Simulate development.
    $this->pass('Verifying BigPipe provides useful error output when an error occurs while rendering a placeholder if verbose error logging is enabled.', 'Debug');
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet(Url::fromRoute('big_pipe_test'));
    // The 'edge_case__html_exception' case throws an exception.
    $this->assertRaw('The website encountered an unexpected error. Please try again later');
    $this->assertRaw('You are not allowed to say llamas are not cool!');
    $this->assertNoRaw('</body>', 'Closing body tag absent: error occurred before then.');
    // The exception is expected. Do not interpret it as a test failure.
    unlink(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
  }

  protected function assertBigPipeResponseHeadersPresent() {
    $this->pass('Verifying BigPipe response headers…', 'Debug');
    $this->assertTrue(FALSE !== strpos($this->drupalGetHeader('Cache-Control'), 'private'), 'Cache-Control header set to "private".');
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
    $this->pass('Verifying BigPipe no-JS placeholders & replacements…', 'Debug');
    $this->assertSetsEqual(array_keys($expected_big_pipe_nojs_placeholders), array_map('rawurldecode', explode(' ', $this->drupalGetHeader('BigPipe-Test-No-Js-Placeholders'))));
    foreach ($expected_big_pipe_nojs_placeholders as $big_pipe_nojs_placeholder => $expected_replacement) {
      $this->pass('Checking whether the replacement for the BigPipe no-JS placeholder "' . $big_pipe_nojs_placeholder . '" is present:');
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
   */
  protected function assertBigPipePlaceholders(array $expected_big_pipe_placeholders) {
    $this->pass('Verifying BigPipe placeholders & replacements…', 'Debug');
    $this->assertSetsEqual(array_keys($expected_big_pipe_placeholders), explode(' ', $this->drupalGetHeader('BigPipe-Test-Placeholders')));
    $placeholder_positions = [];
    $placeholder_replacement_positions = [];
    foreach ($expected_big_pipe_placeholders as $big_pipe_placeholder_id => $expected_ajax_response) {
      $this->pass('BigPipe placeholder: ' . $big_pipe_placeholder_id, 'Debug');
      // Verify expected placeholder.
      $expected_placeholder_html = '<div data-big-pipe-placeholder-id="' . $big_pipe_placeholder_id . '"></div>';
      $this->assertRaw($expected_placeholder_html, 'BigPipe placeholder for placeholder ID "' . $big_pipe_placeholder_id . '" found.');
      $pos = strpos($this->getRawContent(), $expected_placeholder_html);
      $placeholder_positions[$pos] = $big_pipe_placeholder_id;
      // Verify expected placeholder replacement.
      $expected_placeholder_replacement = '<script type="application/vnd.drupal-ajax" data-big-pipe-replacement-for-placeholder-with-id="' . $big_pipe_placeholder_id . '">';
      $result = $this->xpath('//script[@data-big-pipe-replacement-for-placeholder-with-id=:id]', [':id' => Html::decodeEntities($big_pipe_placeholder_id)]);
      if ($expected_ajax_response === NULL) {
        $this->assertEqual(0, count($result));
        $this->assertNoRaw($expected_placeholder_replacement);
        continue;
      }
      $this->assertEqual($expected_ajax_response, trim((string) $result[0]));
      $this->assertRaw($expected_placeholder_replacement);
      $pos = strpos($this->getRawContent(), $expected_placeholder_replacement);
      $placeholder_replacement_positions[$pos] = $big_pipe_placeholder_id;
    }
    ksort($placeholder_positions, SORT_NUMERIC);
    $this->assertEqual(array_keys($expected_big_pipe_placeholders), array_values($placeholder_positions));
    $this->assertEqual(count($expected_big_pipe_placeholders), preg_match_all('/' . preg_quote('<div data-big-pipe-placeholder-id="', '/') . '/', $this->getRawContent()));
    $expected_big_pipe_placeholders_with_replacements = array_filter($expected_big_pipe_placeholders);
    $this->assertEqual(array_keys($expected_big_pipe_placeholders_with_replacements), array_values($placeholder_replacement_positions));
    $this->assertEqual(count($expected_big_pipe_placeholders_with_replacements), preg_match_all('/' . preg_quote('<script type="application/vnd.drupal-ajax" data-big-pipe-replacement-for-placeholder-with-id="', '/') . '/', $this->getRawContent()));

    $this->pass('Verifying BigPipe start/stop signals…', 'Debug');
    $this->assertRaw(BigPipe::START_SIGNAL, 'BigPipe start signal present.');
    $this->assertRaw(BigPipe::STOP_SIGNAL, 'BigPipe stop signal present.');
    $start_signal_position = strpos($this->getRawContent(), BigPipe::START_SIGNAL);
    $stop_signal_position = strpos($this->getRawContent(), BigPipe::STOP_SIGNAL);
    $this->assertTrue($start_signal_position < $stop_signal_position, 'BigPipe start signal appears before stop signal.');

    $this->pass('Verifying BigPipe placeholder replacements and start/stop signals were streamed in the correct order…', 'Debug');
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
   * @return \Drupal\big_pipe\Tests\BigPipePlaceholderTestCase[]
   */
  protected function getTestCases() {
    // Ensure we can generate CSRF tokens for the current user's session.
    $session_data = $this->container->get('session_handler.write_safe')->read($this->cookies[$this->getSessionName()]['value']);
    $csrf_token_seed = unserialize(explode('_sf2_meta|', $session_data)[1])['s'];
    $this->container->get('session_manager.metadata_bag')->setCsrfTokenSeed($csrf_token_seed);

    return \Drupal\big_pipe\Tests\BigPipePlaceholderTestCases::cases($this->container, $this->rootUser);
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
    $non_deleted_cookies = array_filter($this->cookies, function ($item) { return $item['value'] !== 'deleted'; });
    $this->assertEqual($expected, isset($non_deleted_cookies[$cookie_name]), $expected ? "$cookie_label cookie exists." : "$cookie_label cookie does not exist.");
  }

  /**
   * Calls ::performMetaRefresh() and asserts the responses.
   */
  protected function assertBigPipeNoJsMetaRefreshRedirect() {
    $original_url = $this->url;
    $this->performMetaRefresh();

    $this->assertEqual($original_url, $this->url, 'Redirected back to the original location.');

    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertEqual(2, count($headers), 'Two requests were made upon following the <meta> refresh, there are headers for two responses.');

    // First response: redirect.
    $this->assertEqual('HTTP/1.1 302 Found', $headers[0][':status'], 'The first response was a 302 (redirect).');
    $this->assertIdentical(0, strpos($headers[0]['set-cookie'], 'big_pipe_nojs=1'), 'The first response sets the big_pipe_nojs cookie.');
    $this->assertEqual($original_url, $headers[0]['location'], 'The first response redirected back to the original page.');
    $this->assertTrue(empty(array_diff(['cookies:big_pipe_nojs', 'session.exists'], explode(' ', $headers[0]['x-drupal-cache-contexts']))), 'The first response varies by the "cookies:big_pipe_nojs" and "session.exists" cache contexts.');
    $this->assertFalse(isset($headers[0]['surrogate-control']), 'The first response has no "Surrogate-Control" header.');

    // Second response: redirect followed.
    $this->assertEqual('HTTP/1.1 200 OK', $headers[1][':status'], 'The second response was a 200.');
    $this->assertTrue(empty(array_diff(['cookies:big_pipe_nojs', 'session.exists'], explode(' ', $headers[0]['x-drupal-cache-contexts']))), 'The first response varies by the "cookies:big_pipe_nojs" and "session.exists" cache contexts.');
    $this->assertEqual('no-store, content="BigPipe/1.0"', $headers[1]['surrogate-control'], 'The second response has a "Surrogate-Control" header.');

    $this->assertNoRaw('<noscript><meta http-equiv="Refresh" content="0; URL=', 'Once the BigPipe no-JS cookie is set, the <meta> refresh is absent: only one redirect ever happens.');
  }

}
