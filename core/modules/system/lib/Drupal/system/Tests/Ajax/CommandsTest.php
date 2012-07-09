<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\CommandsTest.
 */

namespace Drupal\system\Tests\Ajax;

/**
 * Tests Ajax framework commands.
 */
class CommandsTest extends AjaxTestBase {
  public static function getInfo() {
    return array(
      'name' => 'AJAX commands',
      'description' => 'Performs tests on AJAX framework commands.',
      'group' => 'AJAX',
    );
  }

  /**
   * Test the various Ajax Commands.
   */
  function testAjaxCommands() {
    $form_path = 'ajax_forms_test_ajax_commands_form';
    $web_user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($web_user);

    $edit = array();

    // Tests the 'after' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'After': Click to put something after the div")));
    $expected = array(
      'command' => 'insert',
      'method' => 'after',
      'data' => 'This will be placed after',
    );
    $this->assertCommand($commands, $expected, "'after' AJAX command issued with correct data");

    // Tests the 'alert' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'Alert': Click to alert")));
    $expected = array(
      'command' => 'alert',
      'text' => 'Alert',
    );
    $this->assertCommand($commands, $expected, "'alert' AJAX Command issued with correct text");

    // Tests the 'append' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'Append': Click to append something")));
    $expected = array(
      'command' => 'insert',
      'method' => 'append',
      'data' => 'Appended text',
    );
    $this->assertCommand($commands, $expected, "'append' AJAX command issued with correct data");

    // Tests the 'before' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'before': Click to put something before the div")));
    $expected = array(
      'command' => 'insert',
      'method' => 'before',
      'data' => 'Before text',
    );
    $this->assertCommand($commands, $expected, "'before' AJAX command issued with correct data");

    // Tests the 'changed' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX changed: Click to mark div changed.")));
    $expected = array(
      'command' => 'changed',
      'selector' => '#changed_div',
    );
    $this->assertCommand($commands, $expected, "'changed' AJAX command issued with correct selector");

    // Tests the 'changed' command using the second argument.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX changed: Click to mark div changed with asterisk.")));
    $expected = array(
      'command' => 'changed',
      'selector' => '#changed_div',
      'asterisk' => '#changed_div_mark_this',
    );
    $this->assertCommand($commands, $expected, "'changed' AJAX command (with asterisk) issued with correct selector");

    // Tests the 'css' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("Set the the '#box' div to be blue.")));
    $expected = array(
      'command' => 'css',
      'selector' => '#css_div',
      'argument' => array('background-color' => 'blue'),
    );
    $this->assertCommand($commands, $expected, "'css' AJAX command issued with correct selector");

    // Tests the 'data' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX data command: Issue command.")));
    $expected = array(
      'command' => 'data',
      'name' => 'testkey',
      'value' => 'testvalue',
    );
    $this->assertCommand($commands, $expected, "'data' AJAX command issued with correct key and value");

    // Tests the 'invoke' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX invoke command: Invoke addClass() method.")));
    $expected = array(
      'command' => 'invoke',
      'method' => 'addClass',
      'arguments' => array('error'),
    );
    $this->assertCommand($commands, $expected, "'invoke' AJAX command issued with correct method and argument");

    // Tests the 'html' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX html: Replace the HTML in a selector.")));
    $expected = array(
      'command' => 'insert',
      'method' => 'html',
      'data' => 'replacement text',
    );
    $this->assertCommand($commands, $expected, "'html' AJAX command issued with correct data");

    // Tests the 'insert' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX insert: Let client insert based on #ajax['method'].")));
    $expected = array(
      'command' => 'insert',
      'data' => 'insert replacement text',
    );
    $this->assertCommand($commands, $expected, "'insert' AJAX command issued with correct data");

    // Tests the 'prepend' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'prepend': Click to prepend something")));
    $expected = array(
      'command' => 'insert',
      'method' => 'prepend',
      'data' => 'prepended text',
    );
    $this->assertCommand($commands, $expected, "'prepend' AJAX command issued with correct data");

    // Tests the 'remove' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'remove': Click to remove text")));
    $expected = array(
      'command' => 'remove',
      'selector' => '#remove_text',
    );
    $this->assertCommand($commands, $expected, "'remove' AJAX command issued with correct command and selector");

    // Tests the 'restripe' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'restripe' command")));
    $expected = array(
      'command' => 'restripe',
      'selector' => '#restripe_table',
    );
    $this->assertCommand($commands, $expected, "'restripe' AJAX command issued with correct selector");

    // Tests the 'settings' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'settings' command")));
    $expected = array(
      'command' => 'settings',
      'settings' => array('ajax_forms_test' => array('foo' => 42)),
    );
    $this->assertCommand($commands, $expected, "'settings' AJAX command issued with correct data");

    // Tests the 'add_css' command.
    $commands = $this->drupalPostAJAX($form_path, $edit, array('op' => t("AJAX 'add_css' command")));
    $expected = array(
      'command' => 'add_css',
      'data' => 'my/file.css',
    );
    $this->assertCommand($commands, $expected, "'add_css' AJAX command issued with correct data");
  }
}
