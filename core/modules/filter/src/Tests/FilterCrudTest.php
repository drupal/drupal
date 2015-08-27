<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\FilterCrudTest.
 */

namespace Drupal\filter\Tests;

use Drupal\filter\Entity\FilterFormat;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests creation, loading, updating, deleting of text formats and filters.
 *
 * @group filter
 */
class FilterCrudTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'filter_test', 'system', 'user'];

  /**
   * Tests CRUD operations for text formats and filters.
   */
  function testTextFormatCrud() {
    // Add a text format with minimum data only.
    $format = entity_create('filter_format');
    $format->set('format', 'empty_format');
    $format->set('name', 'Empty format');
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
    $format->set('name', 'Altered format');
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
    $this->assertTrue(!isset($formats[$format->id()]), 'filter_formats: Disabled text format no longer exists.');
  }

  /**
   * Tests disabling the fallback text format.
   */
  public function testDisableFallbackFormat() {
    $this->installConfig(['filter']);
    $message = '\LogicException with message "The fallback text format \'plain_text\' cannot be disabled." was thrown.';
    try {
      FilterFormat::load('plain_text')->disable();
      $this->fail($message);
    }
    catch (\LogicException $e) {
      $this->assertIdentical($e->getMessage(), "The fallback text format 'plain_text' cannot be disabled.", $message);
    }
  }

  /**
   * Verifies that a text format is properly stored.
   */
  function verifyTextFormat($format) {
    $t_args = array('%format' => $format->label());
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Verify the loaded filter has all properties.
    $filter_format = entity_load('filter_format', $format->id());
    $this->assertEqual($filter_format->id(), $format->id(), format_string('filter_format_load: Proper format id for text format %format.', $t_args));
    $this->assertEqual($filter_format->label(), $format->label(), format_string('filter_format_load: Proper title for text format %format.', $t_args));
    $this->assertEqual($filter_format->get('weight'), $format->get('weight'), format_string('filter_format_load: Proper weight for text format %format.', $t_args));
    // Check that the filter was created in site default language.
    $this->assertEqual($format->language()->getId(), $default_langcode, format_string('filter_format_load: Proper language code for text format %format.', $t_args));
  }

}
