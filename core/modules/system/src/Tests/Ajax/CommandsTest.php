<?php

namespace Drupal\system\Tests\Ajax;

use Drupal\Core\Ajax\AddCssCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Performs tests on AJAX framework commands.
 *
 * @group Ajax
 */
class CommandsTest extends AjaxTestBase {

  /**
   * Tests the various Ajax Commands.
   */
  public function testAjaxCommands() {
    $form_path = 'ajax_forms_test_ajax_commands_form';
    $web_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($web_user);

    $edit = [];

    // Tests the 'add_css' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'add_css' command")]);
    $expected = new AddCssCommand('my/file.css');
    $this->assertCommand($commands, $expected->render(), "'add_css' AJAX command issued with correct data.");

    // Tests the 'after' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'After': Click to put something after the div")]);
    $expected = new AfterCommand('#after_div', 'This will be placed after');
    $this->assertCommand($commands, $expected->render(), "'after' AJAX command issued with correct data.");

    // Tests the 'alert' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'Alert': Click to alert")]);
    $expected = new AlertCommand(t('Alert'));
    $this->assertCommand($commands, $expected->render(), "'alert' AJAX Command issued with correct text.");

    // Tests the 'append' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'Append': Click to append something")]);
    $expected = new AppendCommand('#append_div', 'Appended text');
    $this->assertCommand($commands, $expected->render(), "'append' AJAX command issued with correct data.");

    // Tests the 'before' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'before': Click to put something before the div")]);
    $expected = new BeforeCommand('#before_div', 'Before text');
    $this->assertCommand($commands, $expected->render(), "'before' AJAX command issued with correct data.");

    // Tests the 'changed' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX changed: Click to mark div changed.")]);
    $expected = new ChangedCommand('#changed_div');
    $this->assertCommand($commands, $expected->render(), "'changed' AJAX command issued with correct selector.");

    // Tests the 'changed' command using the second argument.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX changed: Click to mark div changed with asterisk.")]);
    $expected = new ChangedCommand('#changed_div', '#changed_div_mark_this');
    $this->assertCommand($commands, $expected->render(), "'changed' AJAX command (with asterisk) issued with correct selector.");

    // Tests the 'css' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("Set the '#box' div to be blue.")]);
    $expected = new CssCommand('#css_div', ['background-color' => 'blue']);
    $this->assertCommand($commands, $expected->render(), "'css' AJAX command issued with correct selector.");

    // Tests the 'data' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX data command: Issue command.")]);
    $expected = new DataCommand('#data_div', 'testkey', 'testvalue');
    $this->assertCommand($commands, $expected->render(), "'data' AJAX command issued with correct key and value.");

    // Tests the 'html' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX html: Replace the HTML in a selector.")]);
    $expected = new HtmlCommand('#html_div', 'replacement text');
    $this->assertCommand($commands, $expected->render(), "'html' AJAX command issued with correct data.");

    // Tests the 'insert' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX insert: Let client insert based on #ajax['method'].")]);
    $expected = new InsertCommand('#insert_div', 'insert replacement text');
    $this->assertCommand($commands, $expected->render(), "'insert' AJAX command issued with correct data.");

    // Tests the 'invoke' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX invoke command: Invoke addClass() method.")]);
    $expected = new InvokeCommand('#invoke_div', 'addClass', ['error']);
    $this->assertCommand($commands, $expected->render(), "'invoke' AJAX command issued with correct method and argument.");

    // Tests the 'prepend' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'prepend': Click to prepend something")]);
    $expected = new PrependCommand('#prepend_div', 'prepended text');
    $this->assertCommand($commands, $expected->render(), "'prepend' AJAX command issued with correct data.");

    // Tests the 'remove' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'remove': Click to remove text")]);
    $expected = new RemoveCommand('#remove_text');
    $this->assertCommand($commands, $expected->render(), "'remove' AJAX command issued with correct command and selector.");

    // Tests the 'restripe' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'restripe' command")]);
    $expected = new RestripeCommand('#restripe_table');
    $this->assertCommand($commands, $expected->render(), "'restripe' AJAX command issued with correct selector.");

    // Tests the 'settings' command.
    $commands = $this->drupalPostAjaxForm($form_path, $edit, ['op' => t("AJAX 'settings' command")]);
    $expected = new SettingsCommand(['ajax_forms_test' => ['foo' => 42]]);
    $this->assertCommand($commands, $expected->render(), "'settings' AJAX command issued with correct data.");
  }

  /**
   * Regression test: Settings command exists regardless of JS aggregation.
   */
  public function testAttachedSettings() {
    $assert = function ($message) {
      $response = new AjaxResponse();
      $response->setAttachments([
        'library' => ['core/drupalSettings'],
        'drupalSettings' => ['foo' => 'bar'],
      ]);

      $ajax_response_attachments_processor = \Drupal::service('ajax_response.attachments_processor');
      $subscriber = new AjaxResponseSubscriber($ajax_response_attachments_processor);
      $event = new FilterResponseEvent(
        \Drupal::service('http_kernel'),
        new Request(),
        HttpKernelInterface::MASTER_REQUEST,
        $response
      );
      $subscriber->onResponse($event);
      $expected = [
        'command' => 'settings',
      ];
      $this->assertCommand($response->getCommands(), $expected, $message);
    };

    $config = $this->config('system.performance');

    $config->set('js.preprocess', FALSE)->save();
    $assert('Settings command exists when JS aggregation is disabled.');

    $config->set('js.preprocess', TRUE)->save();
    $assert('Settings command exists when JS aggregation is enabled.');
  }

}
