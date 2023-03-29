<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for revision ids in field aliases.
 *
 * @see views_post_update_fix_revision_id_part()
 *
 * @group Update
 * @group legacy
 */
class ViewsFixRevisionIdUpdateTest extends UpdatePathTestBase {

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
   * Tests the upgrade path for revision ids in field aliases.
   */
  public function testViewsPostUpdateFixRevisionId() {
    $view = View::load('test_fix_revision_id_update');
    $data = $view->toArray();
    $fields = $data['display']['default']['display_options']['fields'];

    $this->assertArrayHasKey('field_test-revision_id_1', $fields);
    $this->assertEquals('field_test-revision_id_1', $fields['field_test-revision_id_1']['id']);
    $this->assertEquals('field_test-revision_id', $fields['field_test-revision_id_1']['field']);
    $this->assertEquals('Replace: {{ field_test-revision_id_1 }}', $fields['field_test-revision_id_1']['alter']['text']);

    $this->assertArrayHasKey('field_test-revision_id_2', $fields);
    $this->assertEquals('field_test-revision_id_2', $fields['field_test-revision_id_2']['id']);
    $this->assertEquals('field_test-revision_id', $fields['field_test-revision_id_2']['field']);
    $this->assertEquals('field_test-revision_id_2: {{ field_test-revision_id_2 }}', $fields['field_test-revision_id_2']['alter']['text']);

    $this->runUpdates();

    $view = View::load('test_fix_revision_id_update');
    $data = $view->toArray();
    $fields = $data['display']['default']['display_options']['fields'];

    $this->assertArrayNotHasKey('field_test-revision_id_1', $fields);
    $this->assertArrayHasKey('field_test__revision_id_1', $fields);
    $this->assertEquals('field_test__revision_id_1', $fields['field_test__revision_id_1']['id']);
    $this->assertEquals('field_test__revision_id', $fields['field_test__revision_id_1']['field']);
    $this->assertEquals('Replace: {{ field_test__revision_id_1 }}', $fields['field_test__revision_id_1']['alter']['text']);

    $this->assertArrayNotHasKey('field_test-revision_id_2', $fields);
    $this->assertArrayHasKey('field_test__revision_id_2', $fields);
    $this->assertEquals('field_test__revision_id_2', $fields['field_test__revision_id_2']['id']);
    $this->assertEquals('field_test__revision_id', $fields['field_test__revision_id_2']['field']);
    $this->assertEquals('field_test-revision_id_2: {{ field_test__revision_id_2 }}', $fields['field_test__revision_id_2']['alter']['text']);

  }

}
