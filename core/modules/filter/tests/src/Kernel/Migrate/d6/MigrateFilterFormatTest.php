<?php

namespace Drupal\Tests\filter\Kernel\Migrate\d6;

use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to filter.formats.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateFilterFormatTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d6_filter_format');
  }

  /**
   * Tests the Drupal 6 filter format to Drupal 8 migration.
   */
  public function testFilterFormat() {
    $filter_format = FilterFormat::load('filtered_html');

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
    $this->assertSame('<a href hreflang> <em> <strong> <cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd>', $filters['filter_html']['settings']['allowed_html']);
    $this->assertTrue($filters['filter_html']['settings']['filter_html_help']);
    $this->assertFalse($filters['filter_html']['settings']['filter_html_nofollow']);
    $this->assertSame(72, $filters['filter_url']['settings']['filter_url_length']);

    // Assert that the php_code format was migrated with filter_null in the
    // php_code filter's place.
    $filter_format = FilterFormat::load('php_code');
    $this->assertInstanceOf(FilterFormatInterface::class, $filter_format);
    $filters = $filter_format->get('filters');
    $this->assertArrayHasKey('filter_null', $filters);
    $this->assertArrayNotHasKey('php_code', $filters);
  }

}
