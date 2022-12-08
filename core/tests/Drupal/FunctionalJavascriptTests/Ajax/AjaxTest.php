<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests AJAX responses.
 *
 * @group Ajax
 */
class AjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testAjaxWithAdminRoute() {
    \Drupal::service('theme_installer')->install(['stable9', 'claro']);
    $theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $theme_config->set('admin', 'claro');
    $theme_config->set('default', 'stable9');
    $theme_config->save();

    $account = $this->drupalCreateUser(['view the administration theme']);
    $this->drupalLogin($account);

    // First visit the site directly via the URL. This should render it in the
    // admin theme.
    $this->drupalGet('admin/ajax-test/theme');
    $assert = $this->assertSession();
    $assert->pageTextContains('Current theme: claro');

    // Now click the modal, which should also use the admin theme.
    $this->drupalGet('ajax-test/dialog');
    $assert->pageTextNotContains('Current theme: stable9');
    $this->clickLink('Link 8 (ajax)');
    $assert->assertWaitOnAjaxRequest();

    $assert->pageTextContains('Current theme: stable9');
    $assert->pageTextNotContains('Current theme: claro');
  }

  /**
   * Tests that AJAX loaded libraries are not retained between requests.
   *
   * @see https://www.drupal.org/node/2647916
   */
  public function testDrupalSettingsCachingRegression() {
    $this->drupalGet('ajax-test/dialog');
    $assert = $this->assertSession();
    $session = $this->getSession();

    // Insert a fake library into the already loaded library settings.
    $fake_library = 'fakeLibrary/fakeLibrary';
    $session->evaluateScript("drupalSettings.ajaxPageState.libraries = drupalSettings.ajaxPageState.libraries + ',$fake_library';");

    $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
    // Test that the fake library is set.
    $this->assertStringContainsString($fake_library, $libraries);

    // Click on the AJAX link.
    $this->clickLink('Link 8 (ajax)');
    $assert->assertWaitOnAjaxRequest();

    // Test that the fake library is still set after the AJAX call.
    $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
    $this->assertStringContainsString($fake_library, $libraries);

    // Reload the page, this should reset the loaded libraries and remove the
    // fake library.
    $this->drupalGet('ajax-test/dialog');
    $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
    $this->assertStringNotContainsString($fake_library, $libraries);

    // Click on the AJAX link again, and the libraries should still not contain
    // the fake library.
    $this->clickLink('Link 8 (ajax)');
    $assert->assertWaitOnAjaxRequest();
    $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
    $this->assertStringNotContainsString($fake_library, $libraries);
  }

  /**
   * Tests that various AJAX responses with DOM elements are correctly inserted.
   *
   * After inserting DOM elements, Drupal JavaScript behaviors should be
   * reattached and all top-level elements of type Node.ELEMENT_NODE need to be
   * part of the context.
   */
  public function testInsertAjaxResponse() {
    $render_single_root = [
      'pre-wrapped-div' => '<div class="pre-wrapped">pre-wrapped<script> var test;</script></div>',
      'pre-wrapped-span' => '<span class="pre-wrapped">pre-wrapped<script> var test;</script></span>',
      'pre-wrapped-whitespace' => ' <div class="pre-wrapped-whitespace">pre-wrapped-whitespace</div>' . "\n",
      'not-wrapped' => 'not-wrapped',
      'comment-string-not-wrapped' => '<!-- COMMENT -->comment-string-not-wrapped',
      'comment-not-wrapped' => '<!-- COMMENT --><div class="comment-not-wrapped">comment-not-wrapped</div>',
      'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect x="0" y="0" height="10" width="10" fill="green"></rect></svg>',
      'empty' => '',
    ];
    $render_multiple_root_unwrapper = [
      'mixed' => ' foo <!-- COMMENT -->  foo bar<div class="a class"><p>some string</p></div> additional not wrapped strings, <!-- ANOTHER COMMENT --> <p>final string</p>',
      'top-level-only' => '<div>element #1</div><div>element #2</div>',
      'top-level-only-pre-whitespace' => ' <div>element #1</div><div>element #2</div> ',
      'top-level-only-middle-whitespace-span' => '<span>element #1</span> <span>element #2</span>',
      'top-level-only-middle-whitespace-div' => '<div>element #1</div> <div>element #2</div>',
    ];

    // This is temporary behavior for BC reason.
    $render_multiple_root_wrapper = [];
    foreach ($render_multiple_root_unwrapper as $key => $render) {
      $render_multiple_root_wrapper["$key--effect"] = '<div>' . $render . '</div>';
    }

    $expected_renders = array_merge(
      $render_single_root,
      $render_multiple_root_wrapper,
      $render_multiple_root_unwrapper
    );

    // Checking default process of wrapping Ajax content.
    foreach ($expected_renders as $render_type => $expected) {
      $this->assertInsert($render_type, $expected);
    }

    // Checking custom ajaxWrapperMultipleRootElements wrapping.
    $custom_wrapper_multiple_root = <<<JS
    (function($, Drupal){
      Drupal.theme.ajaxWrapperMultipleRootElements = function (elements) {
        return $('<div class="my-favorite-div"></div>').append(elements);
      };
    }(jQuery, Drupal));
JS;
    $expected = '<div class="my-favorite-div"><span>element #1</span> <span>element #2</span></div>';
    $this->assertInsert('top-level-only-middle-whitespace-span--effect', $expected, $custom_wrapper_multiple_root);

    // Checking custom ajaxWrapperNewContent wrapping.
    $custom_wrapper_new_content = <<<JS
    (function($, Drupal){
      Drupal.theme.ajaxWrapperNewContent = function (elements) {
        return $('<div class="div-wrapper-forever"></div>').append(elements);
      };
    }(jQuery, Drupal));
JS;
    $expected = '<div class="div-wrapper-forever"></div>';
    $this->assertInsert('empty', $expected, $custom_wrapper_new_content);
  }

  /**
   * Tests that jQuery's global Ajax events are triggered at the correct time.
   */
  public function testGlobalEvents() {
    $session = $this->getSession();
    $assert = $this->assertSession();
    $expected_event_order = implode('', ['ajaxSuccess', 'ajaxComplete', 'ajaxStop']);

    $this->drupalGet('ajax-test/global-events');

    // Ensure that a non-Drupal Ajax request triggers the expected events, in
    // the correct order, a single time.
    $session->executeScript('jQuery.get(Drupal.url("core/COPYRIGHT.txt"))');
    $assert->assertWaitOnAjaxRequest();
    $assert->elementTextEquals('css', '#test_global_events_log', $expected_event_order);
    $assert->elementTextEquals('css', '#test_global_events_log2', $expected_event_order);

    // Ensure that an Ajax request to a Drupal Ajax response, but that was not
    // initiated with Drupal.Ajax(), triggers the expected events, in the
    // correct order, a single time. We expect $expected_event_order to appear
    // twice in each log element, because Drupal Ajax response commands (such
    // as the one to clear the log element) are only executed for requests
    // initiated with Drupal.Ajax(), and these elements already contain the
    // text that was added above.
    $session->executeScript('jQuery.get(Drupal.url("ajax-test/global-events/clear-log"))');
    $assert->assertWaitOnAjaxRequest();
    $assert->elementTextEquals('css', '#test_global_events_log', str_repeat($expected_event_order, 2));
    $assert->elementTextEquals('css', '#test_global_events_log2', str_repeat($expected_event_order, 2));

    // Ensure that a Drupal Ajax request triggers the expected events, in the
    // correct order, a single time.
    // - We expect the first log element to list the events exactly once,
    //   because the Ajax response clears it, and we expect the events to be
    //   triggered after the commands are executed.
    // - We expect the second log element to list the events exactly three
    //   times, because it already contains the two from the code that was
    //   already executed above. This additional log element that isn't cleared
    //   by the response's command ensures that the events weren't triggered
    //   additional times before the response commands were executed.
    $this->click('#test_global_events_drupal_ajax_link');
    $assert->assertWaitOnAjaxRequest();
    $assert->elementTextEquals('css', '#test_global_events_log', $expected_event_order);
    $assert->elementTextEquals('css', '#test_global_events_log2', str_repeat($expected_event_order, 3));
  }

  /**
   * Assert insert.
   *
   * @param string $render_type
   *   Render type.
   * @param string $expected
   *   Expected result.
   * @param string $script
   *   Script for additional theming.
   *
   * @internal
   */
  public function assertInsert(string $render_type, string $expected, string $script = ''): void {
    // Check insert to block element.
    $this->drupalGet('ajax-test/insert-block-wrapper');
    $this->getSession()->executeScript($script);
    $this->clickLink("Link html $render_type");
    $this->assertWaitPageContains('<div class="ajax-target-wrapper"><div id="ajax-target">' . $expected . '</div></div>');

    $this->drupalGet('ajax-test/insert-block-wrapper');
    $this->getSession()->executeScript($script);
    $this->clickLink("Link replaceWith $render_type");
    $this->assertWaitPageContains('<div class="ajax-target-wrapper">' . $expected . '</div>');

    // Check insert to inline element.
    $this->drupalGet('ajax-test/insert-inline-wrapper');
    $this->getSession()->executeScript($script);
    $this->clickLink("Link html $render_type");
    $this->assertWaitPageContains('<div class="ajax-target-wrapper"><span id="ajax-target-inline">' . $expected . '</span></div>');

    $this->drupalGet('ajax-test/insert-inline-wrapper');
    $this->getSession()->executeScript($script);
    $this->clickLink("Link replaceWith $render_type");
    $this->assertWaitPageContains('<div class="ajax-target-wrapper">' . $expected . '</div>');
  }

  /**
   * Asserts that page contains an expected value after waiting.
   *
   * @param string $expected
   *   A needle text.
   *
   * @internal
   */
  protected function assertWaitPageContains(string $expected): void {
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->waitFor(10, function () use ($page, $expected) {
      // Clear content from empty styles and "processed" classes after effect.
      $content = str_replace([' class="processed"', ' processed', ' style=""'], '', $page->getContent());
      return stripos($content, $expected) !== FALSE;
    }), "Page contains expected value: $expected");
  }

}
