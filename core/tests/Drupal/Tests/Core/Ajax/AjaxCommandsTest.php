<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Ajax\AjaxCommandsTest.
 */

namespace Drupal\Tests\Core\Ajax;

use Drupal\Tests\UnitTestCase;
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
 * Tests that each AJAX command object can be created and rendered.
 *
 * @group Ajax
 */
class AjaxCommandsTest extends UnitTestCase {

  /**
   * Tests that AddCssCommand objects can be constructed and rendered.
   */
  public function testAddCssCommand() {
    $command = new AddCssCommand('p{ text-decoration:blink; }');

    $expected = array(
      'command' => 'add_css',
      'data' => 'p{ text-decoration:blink; }',
    );

    $this->assertEquals($command->render(), $expected, "AddCssCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that AfterCommand objecst can be constructed and rendered.
   */
  public function testAfterCommand() {
    $command = new AfterCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'after',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEquals($command->render(), $expected, "AfterCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that AlertCommand objects can be constructed and rendered.
   */
  public function testAlertCommand() {
    $command = new AlertCommand('Set condition 1 throughout the ship!');
    $expected = array(
      'command' => 'alert',
      'text' => 'Set condition 1 throughout the ship!',
    );

    $this->assertEquals($command->render(), $expected, "AlertCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that AppendCommand objects can be constructed and rendered.
   */
  public function testAppendCommand() {
    $command = new AppendCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'append',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEquals($command->render(), $expected, "AppendCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that BeforeCommand objects can be constructed and rendered.
   */
  public function testBeforeCommand() {
    $command = new BeforeCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'before',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEquals($command->render(), $expected, "BeforeCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that ChangedCommand objects can be constructed and rendered.
   */
  public function testChangedCommand() {
    $command = new ChangedCommand('#page-title', '#page-title-changed');

    $expected = array(
      'command' => 'changed',
      'selector' => '#page-title',
      'asterisk' => '#page-title-changed',
    );

    $this->assertEquals($command->render(), $expected, "ChangedCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that CssCommand objects can be constructed and rendered.
   */
  public function testCssCommand() {
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

    $this->assertEquals($command->render(), $expected, "CssCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that DataCommand objects can be constructed and rendered.
   */
  public function testDataCommand() {
    $command = new DataCommand('#page-title', 'my-data', array('key' => 'value'));

    $expected = array(
      'command' => 'data',
      'selector' => '#page-title',
      'name' => 'my-data',
      'value' => array('key' => 'value'),
    );

    $this->assertEquals($command->render(), $expected, "DataCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that HtmlCommand objects can be constructed and rendered.
   */
  public function testHtmlCommand() {
    $command = new HtmlCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'html',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEquals($command->render(), $expected, "HtmlCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that InsertCommand objects can be constructed and rendered.
   */
  public function testInsertCommand() {
    $command = new InsertCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => NULL,
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEquals($command->render(), $expected, "InsertCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that InvokeCommand objects can be constructed and rendered.
   */
  public function testInvokeCommand() {
    $command = new InvokeCommand('#page-title', 'myMethod', array('var1', 'var2'));

    $expected = array(
      'command' => 'invoke',
      'selector' => '#page-title',
      'method' => 'myMethod',
      'args' => array('var1', 'var2'),
    );

    $this->assertEquals($command->render(), $expected, "InvokeCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that PrependCommand objects can be constructed and rendered.
   */
  public function testPrependCommand() {
    $command = new PrependCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'prepend',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEquals($command->render(), $expected, "PrependCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that RemoveCommand objects can be constructed and rendered.
   */
  public function testRemoveCommand() {
    $command = new RemoveCommand('#page-title');

    $expected = array(
      'command' => 'remove',
      'selector' => '#page-title',
    );

    $this->assertEquals($command->render(), $expected, "RemoveCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that ReplaceCommand objects can be constructed and rendered.
   */
  public function testReplaceCommand() {
    $command = new ReplaceCommand('#page-title', '<p>New Text!</p>', array('my-setting' => 'setting'));

    $expected = array(
      'command' => 'insert',
      'method' => 'replaceWith',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => array('my-setting' => 'setting'),
    );

    $this->assertEquals($command->render(), $expected, "ReplaceCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that RestripeCommand objects can be constructed and rendered.
   */
  public function testRestripeCommand() {
    $command = new RestripeCommand('#page-title');

    $expected = array(
      'command' => 'restripe',
      'selector' => '#page-title',
    );

    $this->assertEquals($command->render(), $expected, "RestripeCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that SettingsCommand objects can be constructed and rendered.
   */
  public function testSettingsCommand() {
    $command = new SettingsCommand(array('key' => 'value'), TRUE);

    $expected = array(
      'command' => 'settings',
      'settings' => array('key' => 'value'),
      'merge' => TRUE,
    );

    $this->assertEquals($command->render(), $expected, "SettingsCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that OpenDialogCommand objects can be constructed and rendered.
   */
  public function testOpenDialogCommand() {
    $command = new TestOpenDialogCommand('#some-dialog', 'Title', '<p>Text!</p>', array(
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
    $this->assertEquals($command->render(), $expected, "OpenDialogCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that OpenModalDialogCommand objects can be constructed and rendered.
   */
  public function testOpenModalDialogCommand() {
    $command = new TestOpenModalDialogCommand('Title', '<p>Text!</p>', array(
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
    $this->assertEquals($command->render(), $expected, "OpenModalDialogCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that CloseModalDialogCommand objects can be constructed and rendered.
   */
  public function testCloseModalDialogCommand() {
    $command = new CloseModalDialogCommand();
    $expected = array(
      'command' => 'closeDialog',
      'selector' => '#drupal-modal',
      'persist' => FALSE,
    );

    $this->assertEquals($command->render(), $expected, "CloseModalDialogCommand::render() didn't return the expected array.");
  }

  /**
   * Tests that CloseDialogCommand objects can be constructed and rendered.
   */
  public function testCloseDialogCommand() {
    $command = new CloseDialogCommand('#some-dialog', TRUE);
    $expected = array(
      'command' => 'closeDialog',
      'selector' => '#some-dialog',
      'persist' => TRUE,
    );

    $this->assertEquals($command->render(), $expected, "CloseDialogCommand::render() with a selector and persistence enabled didn't return the expected array.");
  }

  /**
   * Tests that SetDialogOptionCommand objects can be constructed and rendered.
   */
  public function testSetDialogOptionCommand() {
    $command = new SetDialogOptionCommand('#some-dialog', 'width', '500');
    $expected = array(
      'command' => 'setDialogOption',
      'selector' => '#some-dialog',
      'optionName' => 'width',
      'optionValue' => '500',
    );

    $this->assertEquals($command->render(), $expected, "SetDialogOptionCommand::render() with a selector didn't return the expected array.");
  }

  /**
   * Tests that SetDialogTitleCommand objects can be constructed and rendered.
   */
  public function testSetDialogTitleCommand() {
    $command = new SetDialogTitleCommand('#some-dialog', 'Example');
    $expected = array(
      'command' => 'setDialogOption',
      'selector' => '#some-dialog',
      'optionName' => 'title',
      'optionValue' => 'Example',
    );

    $this->assertEquals($command->render(), $expected, "SetDialogTitleCommand::render() with a selector didn't return the expected array.");
  }

  /**
   * Tests that RedirectCommand objects can be constructed and rendered.
   */
  public function testRedirectCommand() {
    $command = new RedirectCommand('http://example.com');
    $expected = array(
      'command' => 'redirect',
      'url' => 'http://example.com',
    );

    $this->assertEquals($command->render(), $expected, "RedirectCommand::render() didn't return the expected command array.");
  }

}

/**
 * Wraps OpenModalDialogCommand::drupalAttachLibrary().
 *
 * {@inheritdoc}
 */
class TestOpenModalDialogCommand extends OpenModalDialogCommand {

  protected function drupalAttachLibrary($name) {
  }

}

/**
 * Wraps OpenDialogCommand::drupalAttachLibrary().
 *
 * {@inheritdoc}
 */
class TestOpenDialogCommand extends OpenDialogCommand {

  protected function drupalAttachLibrary($name) {
  }

}
