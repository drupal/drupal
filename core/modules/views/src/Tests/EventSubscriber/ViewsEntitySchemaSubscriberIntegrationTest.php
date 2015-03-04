<?php

/**
 * @file
 * Contains \Drupal\views\Tests\EventSubscriber\ViewsEntitySchemaSubscriberIntegrationTest.
 */

namespace Drupal\views\Tests\EventSubscriber;

use Drupal\Core\Entity\EntityTypeEvent;
use Drupal\Core\Entity\EntityTypeEvents;
use Drupal\system\Tests\Entity\EntityDefinitionTestTrait;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber
 *
 * @group Views
 */
class ViewsEntitySchemaSubscriberIntegrationTest extends ViewUnitTestBase {

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
  public static $modules = ['entity_test', 'user', 'text'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_entity_test', 'test_view_entity_test_revision', 'test_view_entity_test_data', 'test_view_entity_test_additional_base_field'];

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The tested event subscriber of views.
   *
   * @var \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber
   */
  protected $eventSubscriber;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->eventDispatcher = $this->container->get('event_dispatcher');
    $this->eventSubscriber = $this->container->get('views.entity_schema_subscriber');
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->entityManager = $this->container->get('entity.manager');
    $this->state = $this->container->get('state');

    $this->database = $this->container->get('database');

    // Install every entity type's schema that wasn't installed in the parent
    // method.
    foreach (array_diff_key($this->entityManager->getDefinitions(), array_flip(array('user', 'entity_test'))) as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }

    $this->installSchema('system', 'key_value_expire');
  }

  /**
   * Tests that views are disabled when an entity type is deleted.
   */
  public function testDeleteEntityType() {
    $entity_storage = $this->entityManager->getStorage('view');

    $views = $entity_storage->loadMultiple();

    // Ensure that all test views exists.
    $this->assertTrue(isset($views['test_view_entity_test']));
    $this->assertTrue(isset($views['test_view_entity_test_revision']));
    $this->assertTrue(isset($views['test_view_entity_test_data']));
    $this->assertTrue(isset($views['test_view_entity_test_additional_base_field']));

    $event = new EntityTypeEvent($this->entityManager->getDefinition('entity_test_update'));
    $this->eventDispatcher->dispatch(EntityTypeEvents::DELETE, $event);

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
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test');

    // Ensure the base table got renamed, so also the views fields.
    $this->assertEqual('entity_test_update_new', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update_new', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_new', $display['display_options']['fields']['name']['table']);
  }

  /**
   * Tests that renaming data tables adapts the views.
   */
  public function testDataTableRename() {
    $this->updateEntityTypeToTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_data');
    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    // Ensure that the data table is used.
    $this->assertEqual('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->renameDataTable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_data');

    // Ensure the data table got renamed, so also the views fields.
    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_data_new', $display['display_options']['fields']['name']['table']);
  }

  /**
   * Tests that renaming revision tables adapts the views.
   */
  public function testRevisionBaseTableRename() {
    $this->updateEntityTypeToRevisionable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');
    $this->assertEqual('entity_test_update_revision', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['name']['table']);

    $this->renameRevisionBaseTable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');

    // Ensure the base table got renamed, so also the views fields.
    $this->assertEqual('entity_test_update_revision_new', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update_revision_new', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_revision_new', $display['display_options']['fields']['name']['table']);
  }

  /**
   * Tests that renaming revision tables adapts the views.
   */
  public function testRevisionDataTableRename() {
    $this->updateEntityTypeToRevisionable();
    // Multiple changes, so we have to invalidate the caches, otherwise
    // the second update will revert the first.
    $this->entityManager->clearCachedDefinitions();
    $this->updateEntityTypeToTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');
    $this->assertEqual('entity_test_update_revision', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_revision_data', $display['display_options']['fields']['name']['table']);

    $this->renameRevisionDataTable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');

    // Ensure the base table got renamed, so also the views fields.
    $this->assertEqual('entity_test_update_revision', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_revision_data_new', $display['display_options']['fields']['name']['table']);
  }

  /**
   * Tests that adding data tables adapts the views.
   */
  public function testDataTableAddition() {
    $this->updateEntityTypeToTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test');

    // Ensure the data table got renamed, so also the views fields.
    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_data', $display['display_options']['fields']['name']['table']);
  }

  /**
   * Tests that enabling revisions doesn't do anything.
   */
  public function testRevisionEnabling() {
    $this->updateEntityTypeToRevisionable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test');

    // Ensure that nothing happens.
    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $display = $view->getDisplay('default');
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['name']['table']);
  }

  /**
   * Tests that removing revision support disables the view.
   */
  public function testRevisionDisabling() {
    $this->updateEntityTypeToRevisionable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    $this->updateEntityTypeToNotRevisionable();
    $this->entityDefinitionUpdateManager->applyUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $entity_storage = $this->entityManager->getStorage('view');
    $view = $entity_storage->load('test_view_entity_test_revision');

    $this->assertFalse($view->status());
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
    $this->updateEntityTypeToTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();

    // base + translation <-> base + translation + revision
    $this->updateEntityTypeToTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToRevisionable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotRevisionable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();

    // base + revision <-> base + translation + revision
    $this->updateEntityTypeToRevisionable();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();

    // base <-> base + revision
    $this->updateEntityTypeToRevisionable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotRevisionable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();

    // base <-> base + translation + revision
    $this->updateEntityTypeToRevisionable();
    $this->updateEntityTypeToTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotRevisionable();
    $this->updateEntityTypeToNotTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay();

    $this->assertEqual('entity_test_update', $view->get('base_table'));
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update', $display['display_options']['fields']['name']['table']);
  }

  /**
   * Tests some possible entity table updates for a revision view.
   */
  public function testVariousTableUpdatesForRevisionView() {
    // base + revision <-> base + translation + revision
    $this->updateEntityTypeToRevisionable();
    // Multiple changes, so we have to invalidate the caches, otherwise
    // the second update will revert the first.
    $this->entityManager->clearCachedDefinitions();

    list($view, $display) = $this->getUpdatedViewAndDisplay(TRUE);

    $this->assertEqual('entity_test_update_revision', $view->get('base_table'));
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay(TRUE);

    $this->assertEqual('entity_test_update_revision', $view->get('base_table'));
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_revision_data', $display['display_options']['fields']['name']['table']);

    $this->updateEntityTypeToNotTranslatable();
    $this->entityDefinitionUpdateManager->applyUpdates();
    list($view, $display) = $this->getUpdatedViewAndDisplay(TRUE);

    $this->assertEqual('entity_test_update_revision', $view->get('base_table'));
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['id']['table']);
    $this->assertEqual('entity_test_update_revision', $display['display_options']['fields']['name']['table']);

    $this->resetEntityType();
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
    $entity_storage = $this->entityManager->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $entity_storage->load($revision ? 'test_view_entity_test_revision' : 'test_view_entity_test');
    $display = $view->getDisplay('default');

    return [$view, $display];
  }

}
