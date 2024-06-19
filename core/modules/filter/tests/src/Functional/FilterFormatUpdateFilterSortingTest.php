<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for filter formats.
 *
 * @see views_post_update_fix_revision_id_part()
 *
 * @group Update
 * @group legacy
 */
class FilterFormatUpdateFilterSortingTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests filter_post_update_sort_filters().
   */
  public function testFilterPostUpdateSortFilters(): void {
    $format_config = $this->config('filter.format.plain_text');
    $this->assertSame(['filter_html_escape', 'filter_url', 'filter_autop'], array_keys($format_config->get('filters')));

    $this->runUpdates();

    $format_config = $this->config('filter.format.plain_text');
    $this->assertSame(['filter_autop', 'filter_html_escape', 'filter_url'], array_keys($format_config->get('filters')));
  }

}
