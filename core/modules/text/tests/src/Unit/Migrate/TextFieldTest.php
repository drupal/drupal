<?php

/**
 * @file
 * Contains \Drupal\Tests\text\Unit\Migrate\TextFieldTest.
 */

namespace Drupal\Tests\text\Unit\Migrate;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\text\Plugin\migrate\cckfield\TextField;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\text\Plugin\migrate\cckfield\TextField
 * @group text
 */
class TextFieldTest extends UnitTestCase {

  /**
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface
   */
  protected $plugin;

  /**
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->plugin = new TextField([], 'text', []);

    $migration = $this->prophesize(MigrationInterface::class);

    // The plugin's processCckFieldValues() method will call
    // setProcessOfProperty() and return nothing. So, in order to examine the
    // process pipeline created by the plugin, we need to ensure that
    // getProcess() always returns the last input to setProcessOfProperty().
    $migration->setProcessOfProperty(Argument::type('string'), Argument::type('array'))
      ->will(function($arguments) use ($migration) {
        $migration->getProcess()->willReturn($arguments[1]);
      });

    $this->migration = $migration->reveal();
  }

  /**
   * @covers ::processCckFieldValues
   */
  public function testProcessFilteredTextFieldValues() {
    $field_info = [
      'widget_type' => 'text_textfield',
    ];
    $this->plugin->processCckFieldValues($this->migration, 'body', $field_info);

    $process = $this->migration->getProcess();
    $this->assertSame('iterator', $process['plugin']);
    $this->assertSame('body', $process['source']);
    $this->assertSame('value', $process['process']['value']);

    // Ensure that filter format IDs will be looked up in the filter format
    // migrations.
    $lookup = $process['process']['format'][2];
    $this->assertSame('migration', $lookup['plugin']);
    $this->assertContains('d6_filter_format', $lookup['migration']);
    $this->assertContains('d7_filter_format', $lookup['migration']);
    $this->assertSame('format', $lookup['source']);
  }

  /**
   * @covers ::processCckFieldValues
   */
  public function testProcessBooleanTextImplicitValues() {
    $info = array(
      'widget_type' => 'optionwidgets_onoff',
      'global_settings' => array(
        'allowed_values' => "foo\nbar",
      )
    );
    $this->plugin->processCckFieldValues($this->migration, 'field', $info);

    $expected = [
      'value' => [
        'plugin' => 'static_map',
        'source' => 'value',
        'default_value' => 0,
        'map' => [
          'bar' => 1,
        ],
      ],
    ];
    $this->assertSame($expected, $this->migration->getProcess()['process']);
  }

  /**
   * @covers ::processCckFieldValues
   */
  public function testProcessBooleanTextExplicitValues() {
    $info = array(
      'widget_type' => 'optionwidgets_onoff',
      'global_settings' => array(
        'allowed_values' => "foo|Foo\nbaz|Baz",
      )
    );
    $this->plugin->processCckFieldValues($this->migration, 'field', $info);

    $expected = [
      'value' => [
        'plugin' => 'static_map',
        'source' => 'value',
        'default_value' => 0,
        'map' => [
          'baz' => 1,
        ],
      ],
    ];
    $this->assertSame($expected, $this->migration->getProcess()['process']);
  }

}
