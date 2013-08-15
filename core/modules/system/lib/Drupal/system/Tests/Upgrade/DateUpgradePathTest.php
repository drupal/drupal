<?php

/**
 * @file
 * Contains Drupal\system\Tests\Upgrade\DateUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Test upgrade of date formats.
 */
class DateUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Date upgrade test',
      'description' => 'Upgrade tests for date formats.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.date.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests that date formats have been upgraded.
   */
  public function testDateUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Verify standard date formats.
    $expected_formats['short'] = array(
      'name' => 'Short',
      'pattern' => array(
        'php' => 'm/d/Y - H:i',
      ),
      'locked' => '1',
    );
    $expected_formats['medium'] = array(
      'name' => 'Medium',
      'pattern' => array(
        'php' => 'D, m/d/Y - H:i',
      ),
      'locked' => '1',
    );
    $expected_formats['long'] = array(
      'name' => 'Long',
      'pattern' => array(
        'php' => 'l, j F, Y - H:i',
      ),
      'locked' => '1',
    );

    // Verify custom date format.
    $expected_formats['test_custom'] = array(
      'name' => 'Test Custom',
      'pattern' => array(
        'php' => 'd m Y',
        ),
      'locked' => '0',
    );

    $actual_formats = entity_load_multiple('date_format', array_keys($expected_formats));
    foreach ($expected_formats as $type => $format) {
      $format_info = $actual_formats[$type];
      $this->assertEqual($format_info->label(), $format['name'], format_string('Config value for @type name is the same', array('@type' => $type)));
      $this->assertEqual($format_info->get('locked'), $format['locked'], format_string('Config value for @type locked is the same', array('@type' => $type)));
      $this->assertEqual($format_info->getPattern(), $format['pattern']['php'], format_string('Config value for @type PHP date pattern is the same', array('@type' => $type)));

      // Make sure that the variable was deleted.
      $this->assertNull(update_variable_get('date_format_' . $type), format_string('Date format variable for @type was deleted.', array('@type' => $type)));
    }

    $expected_de_formats = array(
      array(
        'type' => 'long',
        'format' => 'l, j. F, Y - H:i',
      ),
      array(
        'type' => 'medium',
        'format' => 'D, d/m/Y - H:i',
      ),
      array(
        'type' => 'short',
        'format' => 'd/m/Y - H:i',
      ),
    );

    foreach ($expected_de_formats as $locale_format) {
      $format = \Drupal::config('locale.config.de.system.date_format.' . $locale_format['type'])->get('pattern.php');
      $this->assertEqual($locale_format['format'], $format);
    }
  }

}
