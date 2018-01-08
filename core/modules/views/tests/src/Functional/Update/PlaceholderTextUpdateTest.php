<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for placeholder text.
 *
 * @see views_post_update_filter_placeholder_text()
 *
 * @group Update
 */
class PlaceholderTextUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/filter-placeholder-text.php',
    ];
  }

  /**
   * Tests that boolean filter values are updated properly.
   */
  public function testViewsPostUpdatePlaceholderText() {
    $this->runUpdates();

    // Load and initialize our test view.
    $view = View::load('placeholder_text_test');
    $data = $view->toArray();
    // Check that new settings exist.
    $this->assertArrayHasKey('placeholder', $data['display']['default']['display_options']['filters']['title']['expose']);
    $this->assertArrayHasKey('placeholder', $data['display']['default']['display_options']['filters']['created']['expose']);
    $this->assertArrayHasKey('min_placeholder', $data['display']['default']['display_options']['filters']['created']['expose']);
    $this->assertArrayHasKey('max_placeholder', $data['display']['default']['display_options']['filters']['created']['expose']);
  }

}
