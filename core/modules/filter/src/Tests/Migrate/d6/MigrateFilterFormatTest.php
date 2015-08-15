<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\Migrate\d6\MigrateFilterFormatTest.
 */

namespace Drupal\filter\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to filter.formats.*.yml.
 *
 * @group filter
 */
class MigrateFilterFormatTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  static $modules = array('filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_filter_format');
  }

  /**
   * Tests the Drupal 6 filter format to Drupal 8 migration.
   */
  public function testFilterFormat() {
    $filter_format = entity_load('filter_format', 'filtered_html');

    // Check filter status.
    $filters = $filter_format->get('filters');
    $this->assertTrue($filters['filter_autop']['status']);
    $this->assertTrue($filters['filter_url']['status']);
    $this->assertTrue($filters['filter_htmlcorrector']['status']);
    $this->assertTrue($filters['filter_html']['status']);

    // These should be false by default.
    $this->assertFalse(isset($filters['filter_html_escape']));
    $this->assertFalse(isset($filters['filter_caption']));
    $this->assertFalse(isset($filters['filter_html_image_secure']));

    // Check variables migrated into filter.
    $this->assertIdentical('<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd>', $filters['filter_html']['settings']['allowed_html']);
    $this->assertIdentical(TRUE, $filters['filter_html']['settings']['filter_html_help']);
    $this->assertIdentical(FALSE, $filters['filter_html']['settings']['filter_html_nofollow']);
    $this->assertIdentical(72, $filters['filter_url']['settings']['filter_url_length']);
  }

}
