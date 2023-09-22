<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for adding missing labels.
 *
 * @see views_post_update_add_missing_labels()
 *
 * @group Update
 * @group legacy
 */
class ViewsAddMissingLabelsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/fix-revision-id-update.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installModulesFromClassProperty($this->container);
  }

  /**
   * Tests the upgrade path for adding missing labels.
   */
  public function testViewsPostUpdateFixRevisionId() {
    $view = View::load('test_fix_revision_id_update');
    $data = $view->toArray();
    $this->assertEmpty($data['label']);

    $this->runUpdates();

    $view = View::load('test_fix_revision_id_update');
    $data = $view->toArray();
    $this->assertSame('test_fix_revision_id_update', $data['label']);
  }

}
