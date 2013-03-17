<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\AjaxCommandsUnitTest.
 */

namespace Drupal\system\Tests\Ajax;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Ajax\AddCssCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\SetDialogOptionCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Tests for all AJAX Commands.
 */
class AjaxCommandsUnitTest extends DrupalUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Ajax Command Objects',
      'description' => 'Test that each AJAX command object can be created and rendered',
      'group' => 'AJAX',
    );
  }

  /**
   * Tests that AddCssCommand objects can be constructed and rendered.
   */
  function testAddCssCommand() {

    $command = new AddCssCommand('p{ text-decoration:blink; }');

    $expected = array(
      'command' => 'add_css',
      'data' => 'p{ text-decoration:blink; }',
    );

    $this->assertEqual($command->render(), $expected, 'AddCssCommand::render() returns a proper array.');
  }

  /**
   * Tests that AfterCommand objecst can be constructed and rendered.
   */
  function testAfterCommand() {

    $command = new AfterCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'after',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEqual($command->render(), $expected, 'AfterCommand::render() returns a proper array.');
  }

  /**
   * Tests that AlertCommand objects can be constructed and rendered.
   */
  function testAlertCommand() {
    $command = new AlertCommand('Set condition 1 throughout the ship!');
    $expected = array(
      'command' => 'alert',
      'text' => 'Set condition 1 throughout the ship!',
    );

    $this->assertEqual($command->render(), $expected, 'AlertCommand::render() returns a proper array.');
  }

  /**
   * Tests that AppendCommand objects can be constructed and rendered.
   */
  function testAppendCommand() {
    // Test AppendCommand.
    $command = new AppendCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'append',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEqual($command->render(), $expected, 'AppendCommand::render() returns a proper array.');
  }

  /**
   * Tests that BeforeCommand objects can be constructed and rendered.
   */
  function testBeforeCommand() {

    $command = new BeforeCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'before',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEqual($command->render(), $expected, 'BeforeCommand::render() returns a proper array.');
  }

  /**
   * Tests that ChangedCommand objects can be constructed and rendered.
   */
  function testChangedCommand() {
    $command = new ChangedCommand('#page-title', '#page-title-changed');

    $expected = array(
      'command' => 'changed',
      'selector' => '#page-title',
      'asterisk' => '#page-title-changed',
    );

    $this->assertEqual($command->render(), $expected, 'ChangedCommand::render() returns a proper array.');
  }

  /**
   * Tests that CssCommand objects can be constructed and rendered.
   */
  function testCssCommand() {

    $command = new CssCommand('#page-title', array('text-decoration' => 'blink'));
    $command->setProperty('font-size', '40px')->setProperty('font-weight', 'bold');

    $expected = array(
      'command' => 'css',
      'selector' => '#page-title',
      'argument' => array(
        'text-decoration' => 'blink',
        'font-size' => '40px',
        'font-weight' => 'bold',
      ),
    );

    $this->assertEqual($command->render(), $expected, 'CssCommand::render() returns a proper array.');
  }

  /**
   * Tests that DataCommand objects can be constructed and rendered.
   */
  function testDataCommand() {

    $command = new DataCommand('#page-title', 'my-data', array('key' => 'value'));

    $expected = array(
      'command' => 'data',
      'selector' => '#page-title',
      'name' => 'my-data',
      'value' => array('key' => 'value'),
    );

    $this->assertEqual($command->render(), $expected, 'DataCommand::render() returns a proper array.');
  }

  /**
   * Tests that HtmlCommand objects can be constructed and rendered.
   */
  function testHtmlCommand() {

    $command = new HtmlCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'html',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEqual($command->render(), $expected, 'HtmlCommand::render() returns a proper array.');
  }

  /**
   * Tests that InsertCommand objects can be constructed and rendered.
   */
  function testInsertCommand() {

    $command = new InsertCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => NULL,
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEqual($command->render(), $expected, 'InsertCommand::render() returns a proper array.');
  }

  /**
   * Tests that InvokeCommand objects can be constructed and rendered.
   */
  function testInvokeCommand() {

    $command = new InvokeCommand('#page-title', 'myMethod', array('var1', 'var2'));

    $expected = array(
      'command' => 'invoke',
      'selector' => '#page-title',
      'method' => 'myMethod',
      'args' => array('var1', 'var2'),
    );

    $this->assertEqual($command->render(), $expected, 'InvokeCommand::render() returns a proper array.');
  }

  /**
   * Tests that PrependCommand objects can be constructed and rendered.
   */
  function testPrependCommand() {

    $command = new PrependCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'prepend',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEqual($command->render(), $expected, 'PrependCommand::render() returns a proper array.');
  }

  /**
   * Tests that RemoveCommand objects can be constructed and rendered.
   */
  function testRemoveCommand() {

    $command = new RemoveCommand('#page-title');

    $expected = array(
      'command' => 'remove',
      'selector' => '#page-title',
    );

    $this->assertEqual($command->render(), $expected, 'RemoveCommand::render() returns a proper array.');
  }

  /**
   * Tests that ReplaceCommand objects can be constructed and rendered.
   */
  function testReplaceCommand() {
    $command = new ReplaceCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'replaceWith',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEqual($command->render(), $expected, 'ReplaceCommand::render() returns a proper array.');
  }

  /**
   * Tests that RestripeCommand objects can be constructed and rendered.
   */
  function testRestripeCommand() {
    $command = new RestripeCommand('#page-title');

    $expected = array(
      'command' => 'restripe',
      'selector' => '#page-title',
    );

    $this->assertEqual($command->render(), $expected, 'RestripeCommand::render() returns a proper array.');
  }

  /**
   * Tests that SettingsCommand objects can be constructed and rendered.
   */
  function testSettingsCommand() {
    $command = new SettingsCommand(array('key' => 'value'), TRUE);

    $expected = array(
      'command' => 'settings',
      'settings' => array('key' => 'value'),
      'merge' => TRUE,
    );

    $this->assertEqual($command->render(), $expected, 'SettingsCommand::render() returns a proper array.');
  }

  /**
   * Tests that OpenDialogCommand objects can be constructed and rendered.
   */
  function testOpenDialogCommand() {
    $command = new OpenDialogCommand('#some-dialog', 'Title', '<p>Text!</p>', array(
      'url' => FALSE,
      'width' => 500,
    ));

    $expected = array(
      'command' => 'openDialog',
      'selector' => '#some-dialog',
      'settings' => NULL,
      'data' => '<p>Text!</p>',
      'dialogOptions' => array(
        'url' => FALSE,
        'width' => 500,
        'title' => 'Title',
        'modal' => FALSE,
      ),
    );
    $this->assertEqual($command->render(), $expected, 'OpenDialogCommand::render() returns a proper array.');
  }

  /**
   * Tests that OpenModalDialogCommand objects can be constructed and rendered.
   */
  function testOpenModalDialogCommand() {
    $command = new OpenModalDialogCommand('Title', '<p>Text!</p>', array(
      'url' => 'example',
      'width' => 500,
    ));

    $expected = array(
      'command' => 'openDialog',
      'selector' => '#drupal-modal',
      'settings' => NULL,
      'data' => '<p>Text!</p>',
      'dialogOptions' => array(
        'url' => 'example',
        'width' => 500,
        'title' => 'Title',
        'modal' => TRUE,
      ),
    );
    $this->assertEqual($command->render(), $expected, 'OpenModalDialogCommand::render() returns a proper array.');
  }

  /**
   * Tests that CloseModalDialogCommand objects can be constructed and rendered.
   */
  function testCloseModalDialogCommand() {
    $command = new CloseModalDialogCommand();
    $expected = array(
      'command' => 'closeDialog',
      'selector' => '#drupal-modal',
    );

    $this->assertEqual($command->render(), $expected, 'CloseModalDialogCommand::render() returns a proper array.');
  }

  /**
   * Tests that CloseDialogCommand objects can be constructed and rendered.
   */
  function testCloseDialogCommand() {
    $command = new CloseDialogCommand('#some-dialog');
    $expected = array(
      'command' => 'closeDialog',
      'selector' => '#some-dialog',
    );

    $this->assertEqual($command->render(), $expected, 'CloseDialogCommand::render() with a selector returns a proper array.');
  }

  /**
   * Tests that SetDialogOptionCommand objects can be constructed and rendered.
   */
  function testSetDialogOptionCommand() {
    $command = new SetDialogOptionCommand('#some-dialog', 'width', '500');
    $expected = array(
      'command' => 'setDialogOption',
      'selector' => '#some-dialog',
      'optionName' => 'width',
      'optionValue' => '500',
    );

    $this->assertEqual($command->render(), $expected, 'SetDialogOptionCommand::render() with a selector returns a proper array.');
  }

  /**
   * Tests that SetDialogTitleCommand objects can be constructed and rendered.
   */
  function testSetDialogTitleCommand() {
    $command = new SetDialogTitleCommand('#some-dialog', 'Example');
    $expected = array(
      'command' => 'setDialogOption',
      'selector' => '#some-dialog',
      'optionName' => 'title',
      'optionValue' => 'Example',
    );

    $this->assertEqual($command->render(), $expected, 'SetDialogTitleCommand::render() with a selector returns a proper array.');
  }

  /**
   * Tests that RedirectCommand objects can be constructed and rendered.
   */
  function testRedirectCommand() {
    $command = new RedirectCommand('http://example.com');
    $expected = array(
      'command' => 'redirect',
      'url' => 'http://example.com',
    );

    $this->assertEqual($command->render(), $expected, 'RedirectCommand::render() with the expected command array.');
  }

}
