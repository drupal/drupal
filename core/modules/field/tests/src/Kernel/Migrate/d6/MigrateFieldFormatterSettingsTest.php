<?php

namespace Drupal\Tests\field\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade field formatter settings to entity.display.*.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldFormatterSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
  }

  /**
   * Asserts that a particular component is NOT included in a display.
   *
   * @param string $display_id
   *   The display ID.
   * @param string $component_id
   *   The component ID.
   */
  protected function assertComponentNotExists($display_id, $component_id) {
    $component = EntityViewDisplay::load($display_id)->getComponent($component_id);
    $this->assertNull($component);
  }

  /**
   * Test that migrated entity display settings can be loaded using D8 API's.
   */
  public function testEntityDisplaySettings() {
    // Run tests.
    $field_name = "field_test";
    $expected = [
      'label' => 'above',
      'weight' => 1,
      'type' => 'text_trimmed',
      'settings' => ['trim_length' => 600],
      'third_party_settings' => [],
      'region' => 'content',
    ];

    // Can we load any entity display.
    $display = EntityViewDisplay::load('node.story.teaser');
    $this->assertIdentical($expected, $display->getComponent($field_name));

    // Test migrate worked with multiple bundles.
    $display = EntityViewDisplay::load('node.test_page.teaser');
    $expected['weight'] = 35;
    $this->assertIdentical($expected, $display->getComponent($field_name));

    // Test RSS because that has been converted from 4 to rss.
    $display = EntityViewDisplay::load('node.story.rss');
    $expected['weight'] = 1;
    $this->assertIdentical($expected, $display->getComponent($field_name));

    // Test the default format with text_default which comes from a static map.
    $expected['type'] = 'text_default';
    $expected['settings'] = [];
    $display = EntityViewDisplay::load('node.story.default');
    $this->assertIdentical($expected, $display->getComponent($field_name));

    // Check that we can migrate multiple fields.
    $content = $display->get('content');
    $this->assertTrue(isset($content['field_test']), 'Settings for field_test exist.');
    $this->assertTrue(isset($content['field_test_two']), "Settings for field_test_two exist.");

    // Check that we can migrate a field where exclude is not set.
    $this->assertTrue(isset($content['field_test_exclude_unset']), "Settings for field_test_exclude_unset exist.");

    // Test the number field formatter settings are correct.
    $expected['weight'] = 1;
    $expected['type'] = 'number_integer';
    $expected['settings'] = [
      'thousand_separator' => ',',
      'prefix_suffix' => TRUE,
    ];
    $component = $display->getComponent('field_test_two');
    $this->assertIdentical($expected, $component);
    $expected['weight'] = 2;
    $expected['type'] = 'number_decimal';
    $expected['settings'] = [
       'scale' => 2,
       'decimal_separator' => '.',
       'thousand_separator' => ',',
       'prefix_suffix' => TRUE,
    ];
    $component = $display->getComponent('field_test_three');
    $this->assertIdentical($expected, $component);

    // Test the email field formatter settings are correct.
    $expected['weight'] = 6;
    $expected['type'] = 'email_mailto';
    $expected['settings'] = [];
    $component = $display->getComponent('field_test_email');
    $this->assertIdentical($expected, $component);

    // Test the link field formatter settings.
    $expected['weight'] = 7;
    $expected['type'] = 'link';
    $expected['settings'] = [
      'trim_length' => 80,
      'url_only' => TRUE,
      'url_plain' => TRUE,
      'rel' => '0',
      'target' => '0',
    ];
    $component = $display->getComponent('field_test_link');
    $this->assertIdentical($expected, $component);
    $expected['settings']['url_only'] = FALSE;
    $expected['settings']['url_plain'] = FALSE;
    $display = EntityViewDisplay::load('node.story.teaser');
    $component = $display->getComponent('field_test_link');
    $this->assertIdentical($expected, $component);

    // Test the file field formatter settings.
    $expected['weight'] = 8;
    $expected['type'] = 'file_default';
    $expected['settings'] = [];
    $component = $display->getComponent('field_test_filefield');
    $this->assertIdentical($expected, $component);
    $display = EntityViewDisplay::load('node.story.default');
    $expected['type'] = 'file_url_plain';
    $component = $display->getComponent('field_test_filefield');
    $this->assertIdentical($expected, $component);

    // Test the image field formatter settings.
    $expected['weight'] = 9;
    $expected['type'] = 'image';
    $expected['settings'] = ['image_style' => '', 'image_link' => ''];
    $component = $display->getComponent('field_test_imagefield');
    $this->assertIdentical($expected, $component);
    $display = EntityViewDisplay::load('node.story.teaser');
    $expected['settings']['image_link'] = 'file';
    $component = $display->getComponent('field_test_imagefield');
    $this->assertIdentical($expected, $component);

    // Test phone field.
    $expected['weight'] = 13;
    $expected['type'] = 'basic_string';
    $expected['settings'] = [];
    $component = $display->getComponent('field_test_phone');
    $this->assertIdentical($expected, $component);

    // Test date field.
    $defaults = ['format_type' => 'fallback', 'timezone_override' => ''];
    $expected['weight'] = 10;
    $expected['type'] = 'datetime_default';
    $expected['settings'] = ['format_type' => 'fallback'] + $defaults;
    $component = $display->getComponent('field_test_date');
    $this->assertIdentical($expected, $component);
    $display = EntityViewDisplay::load('node.story.default');
    $expected['settings']['format_type'] = 'long';
    $component = $display->getComponent('field_test_date');
    $this->assertIdentical($expected, $component);

    // Test date stamp field.
    $expected['weight'] = 11;
    $expected['settings']['format_type'] = 'fallback';
    $component = $display->getComponent('field_test_datestamp');
    $this->assertIdentical($expected, $component);
    $display = EntityViewDisplay::load('node.story.teaser');
    $expected['settings'] = ['format_type' => 'medium'] + $defaults;
    $component = $display->getComponent('field_test_datestamp');
    $this->assertIdentical($expected, $component);

    // Test datetime field.
    $expected['weight'] = 12;
    $expected['settings'] = ['format_type' => 'short'] + $defaults;
    $component = $display->getComponent('field_test_datetime');
    $this->assertIdentical($expected, $component);
    $display = EntityViewDisplay::load('node.story.default');
    $expected['settings']['format_type'] = 'fallback';
    $component = $display->getComponent('field_test_datetime');
    $this->assertIdentical($expected, $component);

    // Test a date field with a random format which should be mapped
    // to datetime_default.
    $display = EntityViewDisplay::load('node.story.rss');
    $expected['settings']['format_type'] = 'fallback';
    $component = $display->getComponent('field_test_datetime');
    $this->assertIdentical($expected, $component);
    // Test that our Id map has the correct data.
    $this->assertIdentical(['node', 'story', 'teaser', 'field_test'], $this->getMigration('d6_field_formatter_settings')->getIdMap()->lookupDestinationID(['story', 'teaser', 'node', 'field_test']));

    // Test hidden field.
    $this->assertComponentNotExists('node.test_planet.teaser', 'field_test_text_single_checkbox');

    // Test a node reference field, which should be migrated to an entity
    // reference field.
    $display = EntityViewDisplay::load('node.employee.default');
    $component = $display->getComponent('field_company');
    $this->assertInternalType('array', $component);
    $this->assertSame('entity_reference_label', $component['type']);
    // The default node reference formatter shows the referenced node's title
    // as a link.
    $this->assertTrue($component['settings']['link']);

    $display = EntityViewDisplay::load('node.employee.teaser');
    $component = $display->getComponent('field_company');
    $this->assertInternalType('array', $component);
    $this->assertSame('entity_reference_label', $component['type']);
    // The plain node reference formatter shows the referenced node's title,
    // unlinked.
    $this->assertFalse($component['settings']['link']);
  }

}
