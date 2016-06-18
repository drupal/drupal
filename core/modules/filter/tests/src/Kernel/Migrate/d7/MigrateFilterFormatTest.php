<?php

namespace Drupal\Tests\filter\Kernel\Migrate\d7;

use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to filter.formats.*.yml.
 *
 * @group filter
 */
class MigrateFilterFormatTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('filter');

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
   *   The expected filters in the format, keyed by ID with weight as values.
   * @param int $weight
   *   The weight of the filter.
   */
  protected function assertEntity($id, $label, array $enabled_filters, $weight) {
    /** @var \Drupal\filter\FilterFormatInterface $entity */
    $entity = FilterFormat::load($id);
    $this->assertTrue($entity instanceof FilterFormatInterface);
    $this->assertIdentical($label, $entity->label());
    // get('filters') will return enabled filters only, not all of them.
    $this->assertIdentical(array_keys($enabled_filters), array_keys($entity->get('filters')));
    $this->assertIdentical($weight, $entity->get('weight'));
    foreach ($entity->get('filters') as $filter_id => $filter) {
      $this->assertIdentical($filter['weight'], $enabled_filters[$filter_id]);
    }
  }

  /**
   * Tests the Drupal 7 filter format to Drupal 8 migration.
   */
  public function testFilterFormat() {
    $this->assertEntity('custom_text_format', 'Custom Text format', ['filter_autop' => 0, 'filter_html' => -10], 0);
    $this->assertEntity('filtered_html', 'Filtered HTML', ['filter_autop' => 2, 'filter_html' => 1, 'filter_htmlcorrector' => 10, 'filter_url' => 0], 0);
    $this->assertEntity('full_html', 'Full HTML', ['filter_autop' => 1, 'filter_htmlcorrector' => 10, 'filter_url' => 0], 1);
    $this->assertEntity('plain_text', 'Plain text', ['filter_html_escape' => 0, 'filter_url' => 1, 'filter_autop' => 2], 10);
    // This assertion covers issue #2555089. Drupal 7 formats are identified
    // by machine names, so migrated formats should be merged into existing
    // ones.
    $this->assertNull(FilterFormat::load('plain_text1'));

    // Ensure that filter-specific settings were migrated.
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::load('filtered_html');
    $config = $format->filters('filter_html')->getConfiguration();
    $this->assertIdentical('<div> <span> <ul type> <li> <ol start type> <a href hreflang> <img src alt height width>', $config['settings']['allowed_html']);
    $config = $format->filters('filter_url')->getConfiguration();
    $this->assertIdentical(128, $config['settings']['filter_url_length']);

    // The php_code format gets migrated, but the php_code filter is changed to
    // filter_null.
    $filters = FilterFormat::load('php_code')->get('filters');
    $this->assertTrue(isset($filters['filter_null']));
  }

}
