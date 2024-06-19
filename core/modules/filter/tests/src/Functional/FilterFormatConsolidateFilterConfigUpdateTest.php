<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for filter formats.
 *
 * @see filter_post_update_consolidate_filter_config()
 *
 * @group Update
 * @group legacy
 */
class FilterFormatConsolidateFilterConfigUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../fixtures/update/filter_post_update_consolidate_filter_config-3404431.php',
    ];
  }

  /**
   * @covers \filter_post_update_consolidate_filter_config
   */
  public function testConsolidateFilterConfig(): void {
    $format = $this->config('filter.format.plain_text');
    $this->assertArrayNotHasKey('id', $format->get('filters.filter_autop'));
    $this->assertSame('filter', $format->get('filters.filter_autop.provider'));
    $this->assertSame('filter_html_escape', $format->get('filters.filter_html_escape.id'));
    $this->assertArrayNotHasKey('provider', $format->get('filters.filter_html_escape'));
    $this->assertArrayNotHasKey('id', $format->get('filters.filter_url'));
    $this->assertArrayNotHasKey('provider', $format->get('filters.filter_url'));

    $this->runUpdates();

    $format = $this->config('filter.format.plain_text');
    $this->assertSame('filter_autop', $format->get('filters.filter_autop.id'));
    $this->assertSame('filter', $format->get('filters.filter_autop.provider'));
    $this->assertSame('filter_html_escape', $format->get('filters.filter_html_escape.id'));
    $this->assertSame('filter', $format->get('filters.filter_html_escape.provider'));
    $this->assertSame('filter_url', $format->get('filters.filter_url.id'));
    $this->assertSame('filter', $format->get('filters.filter_url.provider'));
  }

}
