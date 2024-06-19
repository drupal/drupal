<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Ajax;

use Drupal\Core\Ajax\AnnounceCommand;
use Drupal\Core\Asset\AttachedAssets;
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
use Drupal\Core\Ajax\UpdateBuildIdCommand;
use Drupal\Core\Ajax\OpenDialogCommand;

/**
 * Test coverage for various classes in the \Drupal\Core\Ajax namespace.
 *
 * @group Ajax
 */
class AjaxCommandsTest extends UnitTestCase {

  /**
   * @return array
   *   - Array of css elements
   *   - Expected value
   */
  public static function providerCss() {
    return [
      'empty' => [
        [],
        [
          'command' => 'add_css',
          'data' => [],
        ],
      ],
      'single' => [
        [
          [
            'href' => 'core/misc/example.css',
            'media' => 'all',
          ],
        ],
        [
          'command' => 'add_css',
          'data' => [
            [
              'href' => 'core/misc/example.css',
              'media' => 'all',
            ],
          ],
        ],
      ],
      'single-data-property' => [
        [
          [
            'href' => 'core/misc/example.css',
            'media' => 'all',
            'data-test' => 'test',
          ],
        ],
        [
          'command' => 'add_css',
          'data' => [
            [
              'href' => 'core/misc/example.css',
              'media' => 'all',
              'data-test' => 'test',
            ],
          ],
        ],
      ],
      'multiple' => [
        [
          [
            'href' => 'core/misc/example1.css',
            'media' => 'all',
          ],
          [
            'href' => 'core/misc/example2.css',
            'media' => 'all',
          ],
        ],
        [
          'command' => 'add_css',
          'data' => [
            [
              'href' => 'core/misc/example1.css',
              'media' => 'all',
            ],
            [
              'href' => 'core/misc/example2.css',
              'media' => 'all',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers \Drupal\Core\Ajax\AddCssCommand
   * @dataProvider providerCss
   */
  public function testAddCssCommand($css, $expected): void {
    $command = new AddCssCommand($css);

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\AfterCommand
   */
  public function testAfterCommand(): void {
    $command = new AfterCommand('#page-title', '<p>New Text!</p>', ['my-setting' => 'setting']);

    $expected = [
      'command' => 'insert',
      'method' => 'after',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => ['my-setting' => 'setting'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\AlertCommand
   */
  public function testAlertCommand(): void {
    $command = new AlertCommand('Set condition 1 throughout the ship!');
    $expected = [
      'command' => 'alert',
      'text' => 'Set condition 1 throughout the ship!',
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\AnnounceCommand
   *
   * @dataProvider announceCommandProvider
   */
  public function testAnnounceCommand($message, $priority, array $expected): void {
    if ($priority === NULL) {
      $command = new AnnounceCommand($message);
    }
    else {
      $command = new AnnounceCommand($message, $priority);
    }

    $expected_assets = new AttachedAssets();
    $expected_assets->setLibraries(['core/drupal.announce']);

    $this->assertEquals($expected_assets, $command->getAttachedAssets());
    $this->assertSame($expected, $command->render());
  }

  /**
   * Data provider for testAnnounceCommand().
   */
  public static function announceCommandProvider() {
    return [
      'no priority' => [
        'Things are going to change!',
        NULL,
        [
          'command' => 'announce',
          'text' => 'Things are going to change!',
        ],
      ],
      'polite priority' => [
        'Things are going to change!',
        'polite',
        [
          'command' => 'announce',
          'text' => 'Things are going to change!',
          'priority' => AnnounceCommand::PRIORITY_POLITE,
        ],

      ],
      'assertive priority' => [
        'Important!',
        'assertive',
        [
          'command' => 'announce',
          'text' => 'Important!',
          'priority' => AnnounceCommand::PRIORITY_ASSERTIVE,
        ],
      ],
    ];
  }

  /**
   * @covers \Drupal\Core\Ajax\AppendCommand
   */
  public function testAppendCommand(): void {
    $command = new AppendCommand('#page-title', '<p>New Text!</p>', ['my-setting' => 'setting']);

    $expected = [
      'command' => 'insert',
      'method' => 'append',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => ['my-setting' => 'setting'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\BeforeCommand
   */
  public function testBeforeCommand(): void {
    $command = new BeforeCommand('#page-title', '<p>New Text!</p>', ['my-setting' => 'setting']);

    $expected = [
      'command' => 'insert',
      'method' => 'before',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => ['my-setting' => 'setting'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\ChangedCommand
   */
  public function testChangedCommand(): void {
    $command = new ChangedCommand('#page-title', '#page-title-changed');

    $expected = [
      'command' => 'changed',
      'selector' => '#page-title',
      'asterisk' => '#page-title-changed',
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\CssCommand
   */
  public function testCssCommand(): void {
    $command = new CssCommand('#page-title', ['text-decoration' => 'blink']);
    $command->setProperty('font-size', '40px')->setProperty('font-weight', 'bold');

    $expected = [
      'command' => 'css',
      'selector' => '#page-title',
      'argument' => [
        'text-decoration' => 'blink',
        'font-size' => '40px',
        'font-weight' => 'bold',
      ],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\DataCommand
   */
  public function testDataCommand(): void {
    $command = new DataCommand('#page-title', 'my-data', ['key' => 'value']);

    $expected = [
      'command' => 'data',
      'selector' => '#page-title',
      'name' => 'my-data',
      'value' => ['key' => 'value'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\HtmlCommand
   */
  public function testHtmlCommand(): void {
    $command = new HtmlCommand('#page-title', '<p>New Text!</p>', ['my-setting' => 'setting']);

    $expected = [
      'command' => 'insert',
      'method' => 'html',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => ['my-setting' => 'setting'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\InsertCommand
   */
  public function testInsertCommand(): void {
    $command = new InsertCommand('#page-title', '<p>New Text!</p>', ['my-setting' => 'setting']);

    $expected = [
      'command' => 'insert',
      'method' => NULL,
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => ['my-setting' => 'setting'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\InvokeCommand
   */
  public function testInvokeCommand(): void {
    $command = new InvokeCommand('#page-title', 'myMethod', ['var1', 'var2']);

    $expected = [
      'command' => 'invoke',
      'selector' => '#page-title',
      'method' => 'myMethod',
      'args' => ['var1', 'var2'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\PrependCommand
   */
  public function testPrependCommand(): void {
    $command = new PrependCommand('#page-title', '<p>New Text!</p>', ['my-setting' => 'setting']);

    $expected = [
      'command' => 'insert',
      'method' => 'prepend',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => ['my-setting' => 'setting'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\RemoveCommand
   */
  public function testRemoveCommand(): void {
    $command = new RemoveCommand('#page-title');

    $expected = [
      'command' => 'remove',
      'selector' => '#page-title',
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\ReplaceCommand
   */
  public function testReplaceCommand(): void {
    $command = new ReplaceCommand('#page-title', '<p>New Text!</p>', ['my-setting' => 'setting']);

    $expected = [
      'command' => 'insert',
      'method' => 'replaceWith',
      'selector' => '#page-title',
      'data' => '<p>New Text!</p>',
      'settings' => ['my-setting' => 'setting'],
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\RestripeCommand
   */
  public function testRestripeCommand(): void {
    $command = new RestripeCommand('#page-title');

    $expected = [
      'command' => 'restripe',
      'selector' => '#page-title',
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\SettingsCommand
   */
  public function testSettingsCommand(): void {
    $command = new SettingsCommand(['key' => 'value'], TRUE);

    $expected = [
      'command' => 'settings',
      'settings' => ['key' => 'value'],
      'merge' => TRUE,
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\OpenDialogCommand
   */
  public function testOpenDialogCommand(): void {
    $command = new OpenDialogCommand('#some-dialog', 'Title', '<p>Text!</p>', [
      'url' => FALSE,
      'width' => 500,
    ]);

    $expected = [
      'command' => 'openDialog',
      'selector' => '#some-dialog',
      'settings' => NULL,
      'data' => '<p>Text!</p>',
      'dialogOptions' => [
        'url' => FALSE,
        'width' => 500,
        'title' => 'Title',
        'modal' => FALSE,
      ],
    ];
    $this->assertEquals($expected, $command->render());

    $command->setDialogTitle('New title');
    $expected['dialogOptions']['title'] = 'New title';
    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\OpenModalDialogCommand
   */
  public function testOpenModalDialogCommand(): void {
    $command = $this->getMockBuilder('Drupal\Core\Ajax\OpenModalDialogCommand')
      ->setConstructorArgs([
        'Title', '<p>Text!</p>', [
          'url' => 'example',
          'width' => 500,
        ],
      ])
      ->onlyMethods(['getRenderedContent'])
      ->getMock();

    // This method calls the render service, which isn't available. We want it
    // to do nothing so we mock it to return a known value.
    $command->expects($this->once())
      ->method('getRenderedContent')
      ->willReturn('rendered content');

    $expected = [
      'command' => 'openDialog',
      'selector' => '#drupal-modal',
      'settings' => NULL,
      'data' => 'rendered content',
      'dialogOptions' => [
        'url' => 'example',
        'width' => 500,
        'title' => 'Title',
        'modal' => TRUE,
      ],
    ];
    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\CloseModalDialogCommand
   */
  public function testCloseModalDialogCommand(): void {
    $command = new CloseModalDialogCommand();
    $expected = [
      'command' => 'closeDialog',
      'selector' => '#drupal-modal',
      'persist' => FALSE,
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\CloseDialogCommand
   */
  public function testCloseDialogCommand(): void {
    $command = new CloseDialogCommand('#some-dialog', TRUE);
    $expected = [
      'command' => 'closeDialog',
      'selector' => '#some-dialog',
      'persist' => TRUE,
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\SetDialogOptionCommand
   */
  public function testSetDialogOptionCommand(): void {
    $command = new SetDialogOptionCommand('#some-dialog', 'width', '500');
    $expected = [
      'command' => 'setDialogOption',
      'selector' => '#some-dialog',
      'optionName' => 'width',
      'optionValue' => '500',
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\SetDialogTitleCommand
   */
  public function testSetDialogTitleCommand(): void {
    $command = new SetDialogTitleCommand('#some-dialog', 'Example');
    $expected = [
      'command' => 'setDialogOption',
      'selector' => '#some-dialog',
      'optionName' => 'title',
      'optionValue' => 'Example',
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\RedirectCommand
   */
  public function testRedirectCommand(): void {
    $command = new RedirectCommand('http://example.com');
    $expected = [
      'command' => 'redirect',
      'url' => 'http://example.com',
    ];

    $this->assertEquals($expected, $command->render());
  }

  /**
   * @covers \Drupal\Core\Ajax\UpdateBuildIdCommand
   */
  public function testUpdateBuildIdCommand(): void {
    $old = 'ThisStringIsOld';
    $new = 'ThisStringIsNew';
    $command = new UpdateBuildIdCommand($old, $new);
    $expected = [
      'command' => 'update_build_id',
      'old' => $old,
      'new' => $new,
    ];

    $this->assertEquals($expected, $command->render());
  }

}
