<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Performs tests on AJAX framework commands.
 *
 * @group Ajax
 */
class CommandsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'ajax_test', 'ajax_forms_test'];

  /**
   * Tests the various Ajax Commands.
   */
  public function testAjaxCommands() {
    $session = $this->getSession();
    $page = $this->getSession()->getPage();

    $form_path = 'ajax_forms_test_ajax_commands_form';
    $web_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($web_user);
    $this->drupalGet($form_path);

    // Tests the 'add_css' command.
    $page->pressButton("AJAX 'add_css' command");
    $this->assertWaitPageContains('my/file.css');

    // Tests the 'after' command.
    $page->pressButton("AJAX 'After': Click to put something after the div");
    $this->assertWaitPageContains('<div id="after_div">Something can be inserted after this</div>This will be placed after');

    // Tests the 'alert' command.
    $test_alert_command = <<<JS
window.alert = function() {
  document.body.innerHTML += '<div class="alert-command">Alert</div>';
};
JS;
    $session->executeScript($test_alert_command);
    $page->pressButton("AJAX 'Alert': Click to alert");
    $this->assertWaitPageContains('<div class="alert-command">Alert</div>');

    // Tests the 'append' command.
    $page->pressButton("AJAX 'Append': Click to append something");
    $this->assertWaitPageContains('<div id="append_div">Append inside this divAppended text</div>');

    // Tests the 'before' command.
    $page->pressButton("AJAX 'before': Click to put something before the div");
    $this->assertWaitPageContains('Before text<div id="before_div">Insert something before this.</div>');

    // Tests the 'changed' command.
    $page->pressButton("AJAX changed: Click to mark div changed.");
    $this->assertWaitPageContains('<div id="changed_div" class="ajax-changed">');

    // Tests the 'changed' command using the second argument.
    // Refresh page for testing 'changed' command to same element again.
    $this->drupalGet($form_path);
    $page->pressButton("AJAX changed: Click to mark div changed with asterisk.");
    $this->assertWaitPageContains('<div id="changed_div" class="ajax-changed"> <div id="changed_div_mark_this">This div can be marked as changed or not. <abbr class="ajax-changed" title="Changed">*</abbr> </div></div>');

    // Tests the 'css' command.
    $page->pressButton("Set the '#box' div to be blue.");
    $this->assertWaitPageContains('<div id="css_div" style="background-color: blue;">');

    // Tests the 'data' command.
    $page->pressButton("AJAX data command: Issue command.");
    $this->assertTrue($page->waitFor(10, function () use ($session) {
      return 'testvalue' === $session->evaluateScript('window.jQuery("#data_div").data("testkey")');
    }));

    // Tests the 'html' command.
    $page->pressButton("AJAX html: Replace the HTML in a selector.");
    $this->assertWaitPageContains('<div id="html_div">replacement text</div>');

    // Tests the 'insert' command.
    $page->pressButton("AJAX insert: Let client insert based on #ajax['method'].");
    $this->assertWaitPageContains('<div id="insert_div">insert replacement textOriginal contents</div>');

    // Tests the 'invoke' command.
    $page->pressButton("AJAX invoke command: Invoke addClass() method.");
    $this->assertWaitPageContains('<div id="invoke_div" class="error">Original contents</div>');

    // Tests the 'prepend' command.
    $page->pressButton("AJAX 'prepend': Click to prepend something");
    $this->assertWaitPageContains('<div id="prepend_div">prepended textSomething will be prepended to this div. </div>');

    // Tests the 'remove' command.
    $page->pressButton("AJAX 'remove': Click to remove text");
    $this->assertWaitPageContains('<div id="remove_div"></div>');

    // Tests the 'restripe' command.
    $page->pressButton("AJAX 'restripe' command");
    $this->assertWaitPageContains('<tr id="table-first" class="odd"><td>first row</td></tr>');
    $this->assertWaitPageContains('<tr class="even"><td>second row</td></tr>');

    // Tests the 'settings' command.
    $test_settings_command = <<<JS
Drupal.behaviors.testSettingsCommand = {
  attach: function (context, settings) {
    window.jQuery('body').append('<div class="test-settings-command">' + settings.ajax_forms_test.foo + '</div>');
  }
};
JS;
    $session->executeScript($test_settings_command);
    // @todo: Replace after https://www.drupal.org/project/drupal/issues/2616184
    $session->executeScript('window.jQuery("#edit-settings-command-example").mousedown();');
    $this->assertWaitPageContains('<div class="test-settings-command">42</div>');
  }

  /**
   * Asserts that page contains a text after waiting.
   *
   * @param string $text
   *   A needle text.
   */
  protected function assertWaitPageContains($text) {
    $page = $this->getSession()->getPage();
    $page->waitFor(10, function () use ($page, $text) {
      return stripos($page->getContent(), $text) !== FALSE;
    });
    $this->assertContains($text, $page->getContent());
  }

}
