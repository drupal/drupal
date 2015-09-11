<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\Migrate\d7\MigrateFilterFormatTest.
 */

namespace Drupal\filter\Tests\Migrate\d7;

use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to filter.formats.*.yml.
 *
 * @group filter
 */
class MigrateFilterFormatTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  static $modules = array('filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_filter_format');
  }

  /**
   * Asserts various aspects of a filter format entity.
   *
   * @param string $id
   *   The format ID.
   * @param string $label
   *   The expected label of the format.
   * @param array $enabled_filters
   *   The expected filters in the format, keyed by ID.
   */
  protected function assertEntity($id, $label, array $enabled_filters) {
    /** @var \Drupal\filter\FilterFormatInterface $entity */
    $entity = FilterFormat::load($id);
    $this->assertTrue($entity instanceof FilterFormatInterface);
    $this->assertIdentical($label, $entity->label());
    // get('filters') will return enabled filters only, not all of them.
    $this->assertIdentical($enabled_filters, array_keys($entity->get('filters')));
  }

  /**
   * Tests the Drupal 7 filter format to Drupal 8 migration.
   */
  public function testFilterFormat() {
    $this->assertEntity('custom_text_format', 'Custom Text format', ['filter_autop', 'filter_html']);
    $this->assertEntity('filtered_html', 'Filtered HTML', ['filter_autop', 'filter_html', 'filter_htmlcorrector', 'filter_url']);
    $this->assertEntity('full_html', 'Full HTML', ['filter_autop', 'filter_htmlcorrector', 'filter_url']);
    $this->assertEntity('plain_text', 'Plain text', ['filter_html_escape', 'filter_url', 'filter_autop']);
    // This assertion covers issue #2555089. Drupal 7 formats are identified
    // by machine names, so migrated formats should be merged into existing
    // ones.
    $this->assertNull(FilterFormat::load('plain_text1'));

    // Ensure that filter-specific settings were migrated.
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::load('filtered_html');
    $config = $format->filters('filter_html')->getConfiguration();
    $this->assertIdentical('<div> <span> <ul> <li>', $config['settings']['allowed_html']);
    $config = $format->filters('filter_url')->getConfiguration();
    $this->assertIdentical(128, $config['settings']['filter_url_length']);

    // The php_code format gets migrated, but the php_code filter is changed to
    // filter_null.
    $filters = FilterFormat::load('php_code')->get('filters');
    $this->assertTrue(isset($filters['filter_null']));
  }

}
