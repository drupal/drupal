<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\AjaxCommandsUnitTest.
 */

namespace Drupal\system\Tests\Ajax;

use Drupal\simpletest\UnitTestBase;
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

/**
 * Tests for all AJAX Commands.
 */
class AjaxCommandsUnitTest extends UnitTestBase {

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

}

