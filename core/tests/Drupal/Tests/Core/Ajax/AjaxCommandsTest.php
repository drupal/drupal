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
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\SetDialogOptionCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Test coverage for various classes in the \Drupal\Core\Ajax namespace.
 *
 * @group Ajax
 */
class AjaxCommandsTest extends UnitTestCase {

  /**
   * @covers \Drupal\Core\Ajax\AddCssCommand
   */
  public function testAddCssCommand() {
    $command = new AddCssCommand('p{ text-decoration:blink; }');

    $expected = array(
      'command' => 'add_css',
      'data' => 'p{ text-decoration:blink; }',
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\AfterCommand
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

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\AlertCommand
   */
  public function testAlertCommand() {
    $command = new AlertCommand('Set condition 1 throughout the ship!');
    $expected = array(
      'command' => 'alert',
      'text' => 'Set condition 1 throughout the ship!',
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\AppendCommand
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

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\BeforeCommand
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

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\ChangedCommand
   */
  public function testChangedCommand() {
    $command = new ChangedCommand('#page-title', '#page-title-changed');

    $expected = array(
      'command' => 'changed',
      'selector' => '#page-title',
      'asterisk' => '#page-title-changed',
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\CssCommand
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

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\DataCommand
   */
  public function testDataCommand() {
    $command = new DataCommand('#page-title', 'my-data', array('key' => 'value'));

    $expected = array(
      'command' => 'data',
      'selector' => '#page-title',
      'name' => 'my-data',
      'value' => array('key' => 'value'),
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\HtmlCommand
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

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\InsertCommand
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

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\InvokeCommand
   */
  public function testInvokeCommand() {
    $command = new InvokeCommand('#page-title', 'myMethod', array('var1', 'var2'));

    $expected = array(
      'command' => 'invoke',
      'selector' => '#page-title',
      'method' => 'myMethod',
      'args' => array('var1', 'var2'),
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\PrependCommand
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

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\RemoveCommand
   */
  public function testRemoveCommand() {
    $command = new RemoveCommand('#page-title');

    $expected = array(
      'command' => 'remove',
      'selector' => '#page-title',
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\ReplaceCommand
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

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\RestripeCommand
   */
  public function testRestripeCommand() {
    $command = new RestripeCommand('#page-title');

    $expected = array(
      'command' => 'restripe',
      'selector' => '#page-title',
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\SettingsCommand
   */
  public function testSettingsCommand() {
    $command = new SettingsCommand(array('key' => 'value'), TRUE);

    $expected = array(
      'command' => 'settings',
      'settings' => array('key' => 'value'),
      'merge' => TRUE,
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\OpenDialogCommand
   */
  public function testOpenDialogCommand() {
    $command = $this->getMockBuilder('Drupal\Core\Ajax\OpenDialogCommand')
      ->setConstructorArgs(array(
        '#some-dialog', 'Title', '<p>Text!</p>', array(
          'url' => FALSE,
          'width' => 500,
        ),
      ))
      ->setMethods(array('getRenderedContent'))
      ->getMock();

    // This method calls the render service, which isn't available. We want it
    // to do nothing so we mock it to return a known value.
    $command->expects($this->once())
      ->method('getRenderedContent')
      ->willReturn('rendered content');

    $expected = array(
      'command' => 'openDialog',
      'selector' => '#some-dialog',
      'settings' => NULL,
      'data' => 'rendered content',
      'dialogOptions' => array(
        'url' => FALSE,
        'width' => 500,
        'title' => 'Title',
        'modal' => FALSE,
      ),
    );
    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\OpenModalDialogCommand
   */
  public function testOpenModalDialogCommand() {
    $command = $this->getMockBuilder('Drupal\Core\Ajax\OpenModalDialogCommand')
      ->setConstructorArgs(array(
        'Title', '<p>Text!</p>', array(
          'url' => 'example',
          'width' => 500,
        ),
      ))
      ->setMethods(array('getRenderedContent'))
      ->getMock();

    // This method calls the render service, which isn't available. We want it
    // to do nothing so we mock it to return a known value.
    $command->expects($this->once())
      ->method('getRenderedContent')
      ->willReturn('rendered content');

    $expected = array(
      'command' => 'openDialog',
      'selector' => '#drupal-modal',
      'settings' => NULL,
      'data' => 'rendered content',
      'dialogOptions' => array(
        'url' => 'example',
        'width' => 500,
        'title' => 'Title',
        'modal' => TRUE,
      ),
    );
    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\CloseModalDialogCommand
   */
  public function testCloseModalDialogCommand() {
    $command = new CloseModalDialogCommand();
    $expected = array(
      'command' => 'closeDialog',
      'selector' => '#drupal-modal',
      'persist' => FALSE,
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\CloseDialogCommand
   */
  public function testCloseDialogCommand() {
    $command = new CloseDialogCommand('#some-dialog', TRUE);
    $expected = array(
      'command' => 'closeDialog',
      'selector' => '#some-dialog',
      'persist' => TRUE,
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\SetDialogOptionCommand
   */
  public function testSetDialogOptionCommand() {
    $command = new SetDialogOptionCommand('#some-dialog', 'width', '500');
    $expected = array(
      'command' => 'setDialogOption',
      'selector' => '#some-dialog',
      'optionName' => 'width',
      'optionValue' => '500',
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\SetDialogTitleCommand
   */
  public function testSetDialogTitleCommand() {
    $command = new SetDialogTitleCommand('#some-dialog', 'Example');
    $expected = array(
      'command' => 'setDialogOption',
      'selector' => '#some-dialog',
      'optionName' => 'title',
      'optionValue' => 'Example',
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\RedirectCommand
   */
  public function testRedirectCommand() {
    $command = new RedirectCommand('http://example.com');
    $expected = array(
      'command' => 'redirect',
      'url' => 'http://example.com',
    );

    $this->assertEquals($expected, $command->render());
  }

}
