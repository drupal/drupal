<?php

namespace Drupal\Tests\views\Kernel\EventSubscriber;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeEvent;
use Drupal\Core\Entity\EntityTypeEvents;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Tests \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber.
 *
 * @group Views
 */
class ViewsEntitySchemaSubscriberIntegrationTest extends ViewsKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'entity_test_update',
    'user',
    'text',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_entity_test', 'test_view_entity_test_revision', 'test_view_entity_test_data', 'test_view_entity_test_additional_base_field'];

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The tested event subscriber of views.
   *
   * @var \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber
   */
  protected $eventSubscriber;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->eventDispatcher = $this->container->get('event_dispatcher');
    $this->eventSubscriber = $this->container->get('views.entity_schema_subscriber');
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->state = $this->container->get('state');

    // Install every entity type's schema that wasn't installed in the parent
    // method.
    foreach (array_diff_key($this->entityTypeManager->getDefinitions(), array_flip(['user', 'entity_test'])) as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $this->installEntitySchema($entity_type_id);
      }
    }
  }

  /**
   * Tests that views are disabled when an entity type is deleted.
   */
  public function testDeleteEntityType() {
    $entity_storage = $this->entityTypeManager->getStorage('view');

    // Make the test entity type revisionable.
    $this->updateEntityTypeToRevisionable(TRUE);

    $views = $entity_storage->loadMultiple();

    // Ensure that all test views exists.
    $this->assertTrue(isset($views['test_view_entity_test']));
    $this->assertTrue(isset($views['test_view_entity_test_revision']));
    $this->assertTrue(isset($views['test_view_entity_test_data']));
    $this->assertTrue(isset($views['test_view_entity_test_additional_base_field']));

    $event = new EntityTypeEvent($this->entityTypeManager->getDefinition('entity_test_update'));
    $this->eventDispatcher->dispatch($event, EntityTypeEvents::DELETE);

    // We expect that views which use 'entity_test_update' as base tables are
    // disabled.
    $views = $entity_storage->loadMultiple();

    // Ensure that all test views still exists after the deletion of the
    // entity type.
    $this->assertTrue(isset($views['test_view_entity_test']));
    $this->assertTrue(isset($views['test_view_entity_test_revision']));
    $this->assertTrue(isset($views['test_view_entity_test_data']));
    $this->assertTrue(isset($views['test_view_entity_test_additional_base_field']));

    // Ensure that they are all disabled.
    $this->assertFalse($views['test_view_entity_test']->status());
    $this->assertFalse($views['test_view_entity_test_revision']->status());
    $this->assertFalse($views['test_view_entity_test_data']->status());
    $this->assertFalse($views['test_view_entity_test_additional_base_field']->status());
  }

  /**
   * Tests that renaming base tables adapts the views.
   */
  public function testBaseTableRename() {
    $this->renameBaseTable();
    $this->applyEntityUpdates('entity_test_update');

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test');

    // Ensure the base table got renamed, so also the views fields.
    $this->assertEquals('entity_test_update_new', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update_new', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_new', $display['display_options']['fields']['name']['table']);

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([
      'test_view_entity_test',
      'test_view_entity_test_data',
      'test_view_entity_test_additional_base_field',
    ]);
  }

  /**
   * Tests that renaming data tables adapts the views.
   */
  public function testDataTableRename() {
    $this->updateEntityTypeToTranslatable(TRUE);

    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_data');
    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    // Ensure that the data table is used.
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->renameDataTable();
    $this->applyEntityUpdates('entity_test_update');

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_data');

    // Ensure the data table got renamed, so also the views fields.
    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data_new', $display['display_options']['fields']['name']['table']);

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([
      'test_view_entity_test',
      'test_view_entity_test_data',
    ]);
  }

  /**
   * Tests that renaming revision tables adapts the views.
   */
  public function testRevisionBaseTableRename() {
    $this->updateEntityTypeToRevisionable(TRUE);

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');
    $this->assertEquals('entity_test_update_revision', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['name']['table']);

    $this->renameRevisionBaseTable();
    $this->applyEntityUpdates('entity_test_update');

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');

    // Ensure the base table got renamed, so also the views fields.
    $this->assertEquals('entity_test_update_revision_new', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update_revision_new', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_revision_new', $display['display_options']['fields']['name']['table']);

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([
      'test_view_entity_test_revision',
    ]);
  }

  /**
   * Tests that renaming revision tables adapts the views.
   */
  public function testRevisionDataTableRename() {
    $this->updateEntityTypeToRevisionableAndTranslatable(TRUE);

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');
    $this->assertEquals('entity_test_update_revision', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_revision_data', $display['display_options']['fields']['name']['table']);

    $this->renameRevisionDataTable();
    $this->applyEntityUpdates('entity_test_update');

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');

    // Ensure the base table got renamed, so also the views fields.
    $this->assertEquals('entity_test_update_revision', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_revision_data_new', $display['display_options']['fields']['name']['table']);

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([
      'test_view_entity_test',
      'test_view_entity_test_revision',
    ]);
  }

  /**
   * Tests that adding data tables adapts the views.
   */
  public function testDataTableAddition() {
    $this->updateEntityTypeToTranslatable(TRUE);

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test');

    // Ensure the data table got renamed, so also the views fields.
    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([
      'test_view_entity_test',
    ]);
  }

  /**
   * Tests that enabling revisions doesn't do anything.
   */
  public function testRevisionEnabling() {
    $this->updateEntityTypeToRevisionable(TRUE);

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test');

    // Ensure that nothing happens.
    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['name']['table']);

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([]);
  }

  /**
   * Tests that removing revision support disables the view.
   */
  public function testRevisionDisabling() {
    $this->updateEntityTypeToRevisionable(TRUE);
    $this->updateEntityTypeToNotRevisionable(TRUE);

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');

    $this->assertFalse($view->status());

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([
      'test_view_entity_test_revision',
    ]);
  }

  /**
   * Tests a bunch possible entity definition table updates.
   */
  public function testVariousTableUpdates() {
    // We want to test the following permutations of entity definition updates:
    // base <-> base + translation
    // base + translation <-> base + translation + revision
    // base + revision <-> base + translation + revision
    // base <-> base + revision
    // base <-> base + translation + revision

    // base <-> base + translation
    $this->updateEntityTypeToTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();

    // base + translation <-> base + translation + revision
    $this->updateEntityTypeToTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToRevisionable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotRevisionable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();

    // base + revision <-> base + translation + revision
    $this->updateEntityTypeToRevisionable();
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();

    // base <-> base + revision
    $this->updateEntityTypeToRevisionable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotRevisionable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();

    // base <-> base + translation + revision
    $this->updateEntityTypeToRevisionable(TRUE);
    $this->updateEntityTypeToTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotRevisionable(TRUE);
    $this->updateEntityTypeToNotTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay();

    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['name']['table']);

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([
      'test_view_entity_test',
      'test_view_entity_test_data',
      'test_view_entity_test_revision',
    ]);
  }

  /**
   * Tests some possible entity table updates for a revision view.
   */
  public function testVariousTableUpdatesForRevisionView() {
    // base + revision <-> base + translation + revision
    $this->updateEntityTypeToRevisionable(TRUE);

    [$view, $display] = $this->getUpdatedViewAndDisplay(TRUE);

    $this->assertEquals('entity_test_update_revision', $view->get('base_table'));
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay(TRUE);

    $this->assertEquals('entity_test_update_revision', $view->get('base_table'));
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_revision_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotTranslatable(TRUE);
    [$view, $display] = $this->getUpdatedViewAndDisplay(TRUE);

    $this->assertEquals('entity_test_update_revision', $view->get('base_table'));
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_revision', $display['display_options']['fields']['name']['table']);

    // Check that only the impacted views have been updated.
    $this->assertUpdatedViews([
      'test_view_entity_test',
      'test_view_entity_test_data',
      'test_view_entity_test_revision',
    ]);
  }

  /**
   * Tests the case when a view could not be updated automatically.
   */
  public function testViewSaveException() {
    $this->renameBaseTable();
    \Drupal::state()->set('entity_test_update.throw_view_exception', 'test_view_entity_test');
    $this->applyEntityUpdates('entity_test_update');

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test');

    // Check that the table names were not updated automatically for the
    // 'test_view_entity_test' view.
    $this->assertEquals('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update', $display['display_options']['fields']['name']['table']);

    // Check that the other two views impacted by the entity update were updated
    // automatically.
    $view = $entity_storage->load('test_view_entity_test_data');
    $this->assertEquals('entity_test_update_new', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update_new', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $view = $entity_storage->load('test_view_entity_test_additional_base_field');
    $this->assertEquals('entity_test_update_new', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEquals('entity_test_update_new', $display['display_options']['fields']['id']['table']);
    $this->assertEquals('entity_test_update_new', $display['display_options']['fields']['new_base_field']['table']);

    $this->assertUpdatedViews([
      'test_view_entity_test_data',
      'test_view_entity_test_additional_base_field',
    ]);
  }

  /**
   * Tests that broken views are handled gracefully.
   */
  public function testBrokenView() {
    $view_id = 'test_view_entity_test';
    $this->state->set('views_test_config.broken_view', $view_id);
    $this->updateEntityTypeToTranslatable(TRUE);

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityTypeManager->getStorage('view');
    $view = $entity_storage->load($view_id);

    // The broken handler should have been removed.
    $display = $view->getDisplay('default');
    $this->assertFalse(isset($display['display_options']['fields']['id_broken']));

    $this->assertUpdatedViews([$view_id]);
  }

  /**
   * Gets a view and its display.
   *
   * @param bool $revision
   *   (optional) TRUE if we want to get a revision view.
   *
   * @return array
   *   An array with the view as first item, and the display as second.
   */
  protected function getUpdatedViewAndDisplay($revision = FALSE) {
    $entity_storage = $this->entityTypeManager->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $entity_storage->load($revision ? 'test_view_entity_test_revision' : 'test_view_entity_test');
    $display = $view->getDisplay('default');

    return [$view, $display];
  }

  /**
   * Checks that the passed-in view IDs were the only ones updated.
   *
   * @param string[] $updated_view_ids
   *   An array of view IDs.
   *
   * @internal
   */
  protected function assertUpdatedViews(array $updated_view_ids): void {
    $all_view_ids = array_keys($this->entityTypeManager->getStorage('view')->loadMultiple());

    $view_save_count = \Drupal::state()->get('views_test_data.view_save_count', []);
    foreach ($all_view_ids as $view_id) {
      if (in_array($view_id, $updated_view_ids, TRUE)) {
        $this->assertTrue(isset($view_save_count[$view_id]), "The $view_id view has been updated.");
      }
      else {
        $this->assertFalse(isset($view_save_count[$view_id]), "The $view_id view has not been updated.");
      }
    }

    // Check that all test cases are updating only a subset of all the available
    // views.
    $this->assertGreaterThan(count($updated_view_ids), count($all_view_ids));
  }

}
