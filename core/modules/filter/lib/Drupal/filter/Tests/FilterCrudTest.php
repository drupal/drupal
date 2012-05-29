<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterCrudTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\WebTestBase;
use stdClass;

/**
 * Tests for text format and filter CRUD operations.
 */
class FilterCrudTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Filter CRUD operations',
      'description' => 'Test creation, loading, updating, deleting of text formats and filters.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp('filter_test');
  }

  /**
   * Test CRUD operations for text formats and filters.
   */
  function testTextFormatCrud() {
    // Add a text format with minimum data only.
    $format = new stdClass();
    $format->format = 'empty_format';
    $format->name = 'Empty format';
    filter_format_save($format);
    $this->verifyTextFormat($format);
    $this->verifyFilters($format);

    // Add another text format specifying all possible properties.
    $format = new stdClass();
    $format->format = 'custom_format';
    $format->name = 'Custom format';
    $format->filters = array(
      'filter_url' => array(
        'status' => 1,
        'settings' => array(
          'filter_url_length' => 30,
        ),
      ),
    );
    filter_format_save($format);
    $this->verifyTextFormat($format);
    $this->verifyFilters($format);

    // Alter some text format properties and save again.
    $format->name = 'Altered format';
    $format->filters['filter_url']['status'] = 0;
    $format->filters['filter_autop']['status'] = 1;
    filter_format_save($format);
    $this->verifyTextFormat($format);
    $this->verifyFilters($format);

    // Add a uncacheable filter and save again.
    $format->filters['filter_test_uncacheable']['status'] = 1;
    filter_format_save($format);
    $this->verifyTextFormat($format);
    $this->verifyFilters($format);

    // Disable the text format.
    filter_format_disable($format);

    $db_format = db_query("SELECT * FROM {filter_format} WHERE format = :format", array(':format' => $format->format))->fetchObject();
    $this->assertFalse($db_format->status, t('Database: Disabled text format is marked as disabled.'));
    $formats = filter_formats();
    $this->assertTrue(!isset($formats[$format->format]), t('filter_formats: Disabled text format no longer exists.'));
  }

  /**
   * Verify that a text format is properly stored.
   */
  function verifyTextFormat($format) {
    $t_args = array('%format' => $format->name);
    // Verify text format database record.
    $db_format = db_select('filter_format', 'ff')
      ->fields('ff')
      ->condition('format', $format->format)
      ->execute()
      ->fetchObject();
    $this->assertEqual($db_format->format, $format->format, t('Database: Proper format id for text format %format.', $t_args));
    $this->assertEqual($db_format->name, $format->name, t('Database: Proper title for text format %format.', $t_args));
    $this->assertEqual($db_format->cache, $format->cache, t('Database: Proper cache indicator for text format %format.', $t_args));
    $this->assertEqual($db_format->weight, $format->weight, t('Database: Proper weight for text format %format.', $t_args));

    // Verify filter_format_load().
    $filter_format = filter_format_load($format->format);
    $this->assertEqual($filter_format->format, $format->format, t('filter_format_load: Proper format id for text format %format.', $t_args));
    $this->assertEqual($filter_format->name, $format->name, t('filter_format_load: Proper title for text format %format.', $t_args));
    $this->assertEqual($filter_format->cache, $format->cache, t('filter_format_load: Proper cache indicator for text format %format.', $t_args));
    $this->assertEqual($filter_format->weight, $format->weight, t('filter_format_load: Proper weight for text format %format.', $t_args));

    // Verify the 'cache' text format property according to enabled filters.
    $filter_info = filter_get_filters();
    $filters = filter_list_format($filter_format->format);
    $cacheable = TRUE;
    foreach ($filters as $name => $filter) {
      // If this filter is not cacheable, update $cacheable accordingly, so we
      // can verify $format->cache after iterating over all filters.
      if ($filter->status && isset($filter_info[$name]['cache']) && !$filter_info[$name]['cache']) {
        $cacheable = FALSE;
        break;
      }
    }
    $this->assertEqual($filter_format->cache, $cacheable, t('Text format contains proper cache property.'));
  }

  /**
   * Verify that filters are properly stored for a text format.
   */
  function verifyFilters($format) {
    // Verify filter database records.
    $filters = db_query("SELECT * FROM {filter} WHERE format = :format", array(':format' => $format->format))->fetchAllAssoc('name');
    $format_filters = $format->filters;
    foreach ($filters as $name => $filter) {
      $t_args = array('%format' => $format->name, '%filter' => $name);

      // Verify that filter status is properly stored.
      $this->assertEqual($filter->status, $format_filters[$name]['status'], t('Database: Proper status for %filter in text format %format.', $t_args));

      // Verify that filter settings were properly stored.
      $this->assertEqual(unserialize($filter->settings), isset($format_filters[$name]['settings']) ? $format_filters[$name]['settings'] : array(), t('Database: Proper filter settings for %filter in text format %format.', $t_args));

      // Verify that each filter has a module name assigned.
      $this->assertTrue(!empty($filter->module), t('Database: Proper module name for %filter in text format %format.', $t_args));

      // Remove the filter from the copy of saved $format to check whether all
      // filters have been processed later.
      unset($format_filters[$name]);
    }
    // Verify that all filters have been processed.
    $this->assertTrue(empty($format_filters), t('Database contains values for all filters in the saved format.'));

    // Verify filter_list_format().
    $filters = filter_list_format($format->format);
    $format_filters = $format->filters;
    foreach ($filters as $name => $filter) {
      $t_args = array('%format' => $format->name, '%filter' => $name);

      // Verify that filter status is properly stored.
      $this->assertEqual($filter->status, $format_filters[$name]['status'], t('filter_list_format: Proper status for %filter in text format %format.', $t_args));

      // Verify that filter settings were properly stored.
      $this->assertEqual($filter->settings, isset($format_filters[$name]['settings']) ? $format_filters[$name]['settings'] : array(), t('filter_list_format: Proper filter settings for %filter in text format %format.', $t_args));

      // Verify that each filter has a module name assigned.
      $this->assertTrue(!empty($filter->module), t('filter_list_format: Proper module name for %filter in text format %format.', $t_args));

      // Remove the filter from the copy of saved $format to check whether all
      // filters have been processed later.
      unset($format_filters[$name]);
    }
    // Verify that all filters have been processed.
    $this->assertTrue(empty($format_filters), t('filter_list_format: Loaded filters contain values for all filters in the saved format.'));
  }
}
