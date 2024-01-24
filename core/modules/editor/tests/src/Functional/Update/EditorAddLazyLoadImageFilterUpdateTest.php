<?php

declare(strict_types=1);

namespace Drupal\Tests\editor\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests adding editor_image_lazy_load filter to editor_file_reference.
 *
 * @group Update
 * @group #slow
 */
class EditorAddLazyLoadImageFilterUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests upgrading filter settings.
   *
   * @see editor_post_update_image_lazy_load()
   */
  public function testUpdateLazyImageLoad(): void {
    $config = $this->config('filter.format.full_html');
    $this->assertArrayNotHasKey('filter_image_lazy_load', $config->get('filters'));

    $this->runUpdates();

    $config = $this->config('filter.format.full_html');
    $filters = $config->get('filters');
    $this->assertArrayHasKey('filter_image_lazy_load', $filters);
    $this->assertEquals($filters['editor_file_reference']['weight'] + 1, $filters['filter_image_lazy_load']['weight']);
  }

}
