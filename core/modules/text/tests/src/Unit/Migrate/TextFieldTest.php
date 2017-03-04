<?php

namespace Drupal\Tests\text\Unit\Migrate;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
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
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
    $info = [
      'widget_type' => 'optionwidgets_onoff',
      'global_settings' => [
        'allowed_values' => "foo\nbar",
      ]
    ];
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
    $info = [
      'widget_type' => 'optionwidgets_onoff',
      'global_settings' => [
        'allowed_values' => "foo|Foo\nbaz|Baz",
      ]
    ];
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

  /**
   * Data provider for testGetFieldType().
   */
  public function getFieldTypeProvider() {
    return [
      ['string_long', 'text_textfield', [
        'text_processing' => FALSE,
      ]],
      ['string', 'text_textfield', [
        'text_processing' => FALSE,
        'max_length' => 128,
      ]],
      ['string_long', 'text_textfield', [
        'text_processing' => FALSE,
        'max_length' => 4096,
      ]],
      ['text_long', 'text_textfield', [
        'text_processing' => TRUE,
      ]],
      ['text', 'text_textfield', [
        'text_processing' => TRUE,
        'max_length' => 128,
      ]],
      ['text_long', 'text_textfield', [
        'text_processing' => TRUE,
        'max_length' => 4096,
      ]],
      ['list_string', 'optionwidgets_buttons'],
      ['list_string', 'optionwidgets_select'],
      ['boolean', 'optionwidgets_onoff'],
      ['text_long', 'text_textarea'],
      [NULL, 'undefined'],
    ];
  }

  /**
   * @covers ::getFieldType
   * @dataProvider getFieldTypeProvider
   */
  public function testGetFieldType($expected_type, $widget_type, array $settings = []) {
    $row = new Row(['widget_type' => $widget_type], ['widget_type' => []]);
    $row->setSourceProperty('global_settings', $settings);
    $this->assertSame($expected_type, $this->plugin->getFieldType($row));
  }

}
