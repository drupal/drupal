<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the views_post_update_sort_identifier() post update.
 *
 * @group views
 * @group legacy
 */
class ViewsSortIdentifiersUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests views_post_update_sort_identifier().
   *
   * @see views_post_update_sort_identifier()
   */
  public function testSortIdentifierPostUpdate(): void {
    $config_factory = \Drupal::configFactory();
    $view = $config_factory->get('views.view.comments_recent');
    $trail = 'display.default.display_options.sorts.created';
    $this->assertArrayNotHasKey('field_identifier', $view->get("{$trail}.expose"));

    $this->runUpdates();

    $view = $config_factory->get('views.view.comments_recent');
    $sort_handler = $view->get($trail);
    $this->assertSame($sort_handler['id'], $sort_handler['expose']['field_identifier']);
  }

}
