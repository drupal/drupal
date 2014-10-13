<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterCrudTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests creation, loading, updating, deleting of text formats and filters.
 *
 * @group filter
 */
class FilterCrudTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'filter_test');

  /**
   * Tests CRUD operations for text formats and filters.
   */
  function testTextFormatCrud() {
    // Add a text format with minimum data only.
    $format = entity_create('filter_format');
    $format->format = 'empty_format';
    $format->name = 'Empty format';
    $format->save();
    $this->verifyTextFormat($format);

    // Add another text format specifying all possible properties.
    $format = entity_create('filter_format', array(
      'format' => 'custom_format',
      'name' => 'Custom format',
    ));
    $format->setFilterConfig('filter_url', array(
      'status' => 1,
      'settings' => array(
        'filter_url_length' => 30,
      ),
    ));
    $format->save();
    $this->verifyTextFormat($format);

    // Alter some text format properties and save again.
    $format->name = 'Altered format';
    $format->setFilterConfig('filter_url', array(
      'status' => 0,
    ));
    $format->setFilterConfig('filter_autop', array(
      'status' => 1,
    ));
    $format->save();
    $this->verifyTextFormat($format);

    // Add a filter_test_replace  filter and save again.
    $format->setFilterConfig('filter_test_replace', array(
      'status' => 1,
    ));
    $format->save();
    $this->verifyTextFormat($format);

    // Disable the text format.
    $format->disable()->save();

    $formats = filter_formats();
    $this->assertTrue(!isset($formats[$format->format]), 'filter_formats: Disabled text format no longer exists.');
  }

  /**
   * Verifies that a text format is properly stored.
   */
  function verifyTextFormat($format) {
    $t_args = array('%format' => $format->name);
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Verify the loaded filter has all properties.
    $filter_format = entity_load('filter_format', $format->format);
    $this->assertEqual($filter_format->format, $format->format, format_string('filter_format_load: Proper format id for text format %format.', $t_args));
    $this->assertEqual($filter_format->name, $format->name, format_string('filter_format_load: Proper title for text format %format.', $t_args));
    $this->assertEqual($filter_format->weight, $format->weight, format_string('filter_format_load: Proper weight for text format %format.', $t_args));
    // Check that the filter was created in site default language.
    $this->assertEqual($format->language()->getId(), $default_langcode, format_string('filter_format_load: Proper language code for text format %format.', $t_args));
  }

}
