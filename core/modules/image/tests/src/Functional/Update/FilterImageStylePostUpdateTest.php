<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests image_post_update_enable_filter_image_style().
 *
 * @group image
 * @group legacy
 */
class FilterImageStylePostUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/test_enable_filter_image_style.php',
    ];
  }

  /**
   * Tests image_post_update_enable_filter_image_style().
   *
   * @see image_post_update_enable_filter_image_style()
   */
  public function testFilterImageStylePostUpdate(): void {
    $config_trail = 'filters.filter_html.settings.allowed_html';

    // A format with an enabled filter_html filter.
    $basic_html = $this->config('filter.format.basic_html');
    // A format with a disabled filter_html filter.
    $full_html = $this->config('filter.format.full_html');
    // A format without a filter_html filter.
    $plain_text = $this->config('filter.format.plain_text');

    // Check that 'basic_html' text format has an enabled 'filter_html' filter,
    // whose 'allowed_html' setting contains an <img ...> tag that is missing
    // the 'data-image-style' attribute.
    $this->assertTrue($basic_html->get('filters.filter_html.status'));
    $this->assertStringNotContainsString('data-image-style', $basic_html->get($config_trail));

    // Check that 'full_html' text format has an disabled 'filter_html' filter,
    // whose 'allowed_html' setting contains an <img ...> tag that is missing
    // the 'data-image-style' attribute.
    $this->assertFalse($full_html->get('filters.filter_html.status'));
    $this->assertStringNotContainsString('data-image-style', $full_html->get($config_trail));

    // Check that 'plain_text' text format is missing an 'filter_html' filter.
    $this->assertNull($plain_text->get('filters.filter_html'));

    // Run updates.
    $this->runUpdates();

    $basic_html = $this->config('filter.format.basic_html');
    $full_html = $this->config('filter.format.full_html');
    $plain_text = $this->config('filter.format.plain_text');

    // Check that 'basic_html' text format 'filter_html' filter was updated.
    $this->assertStringContainsString('data-image-style', $basic_html->get($config_trail));

    // Check that 'full_html' text format 'filter_html' filter was not updated.
    $this->assertStringNotContainsString('data-image-style', $full_html->get($config_trail));

    // Check that 'plain_text' text format is missing the 'filter_html' filter.
    $this->assertNull($plain_text->get('filters.filter_html'));
  }

}
