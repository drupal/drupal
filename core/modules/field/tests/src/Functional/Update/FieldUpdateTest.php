<?php

namespace Drupal\Tests\field\Functional\Update;

use Drupal\Core\Config\Config;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests that field settings are properly updated during database updates.
 *
 * @group field
 */
class FieldUpdateTest extends UpdatePathTestBase {

  use CronRunTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The key-value collection for tracking installed storage schema.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $installedStorageSchema;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The deleted fields repository.
   *
   * @var \Drupal\Core\Field\DeletedFieldsRepositoryInterface
   */
  protected $deletedFieldsRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configFactory = $this->container->get('config.factory');
    $this->database = $this->container->get('database');
    $this->installedStorageSchema = $this->container->get('keyvalue')->get('entity.storage_schema.sql');
    $this->state = $this->container->get('state');
    $this->deletedFieldsRepository = $this->container->get('entity_field.deleted_fields_repository');
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.views_entity_reference_plugins-2429191.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.remove_handler_submit_setting-2715589.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.update_deleted_field_definitions-2931436.php',
    ];
  }

  /**
   * Tests field_update_8001().
   *
   * @see field_update_8001()
   */
  public function testFieldUpdate8001() {
    // Load the 'node.field_image' field storage config, and check that is has
    // a 'target_bundle' setting.
    $config = $this->configFactory->get('field.storage.node.field_image');
    $settings = $config->get('settings');
    $this->assertTrue(array_key_exists('target_bundle', $settings));

    // Run updates.
    $this->runUpdates();

    // Reload the config, and check that the 'target_bundle' setting has been
    // removed.
    $config = $this->configFactory->get('field.storage.node.field_image');
    $settings = $config->get('settings');
    $this->assertFalse(array_key_exists('target_bundle', $settings));
  }

  /**
   * Tests field_update_8002().
   *
   * @see field_update_8002()
   */
  public function testFieldUpdate8002() {
    // Check that 'entity_reference' is the provider and a dependency of the
    // test field storage .
    $field_storage = $this->configFactory->get('field.storage.node.field_ref_views_select_2429191');
    $this->assertIdentical($field_storage->get('module'), 'entity_reference');
    $this->assertEntityRefDependency($field_storage, TRUE);

    // Check that 'entity_reference' is a dependency of the test field.
    $field = $this->configFactory->get('field.field.node.article.field_ref_views_select_2429191');
    $this->assertEntityRefDependency($field, TRUE);

    // Check that 'entity_reference' is a dependency of the test view.
    $view = $this->configFactory->get('views.view.entity_reference_plugins_2429191');
    $this->assertEntityRefDependency($view, TRUE);

    // Run updates.
    $this->runUpdates();

    // Check that 'entity_reference' is no longer a dependency of the test field
    // and view.
    $field_storage = $this->configFactory->get('field.storage.node.field_ref_views_select_2429191');
    $this->assertIdentical($field_storage->get('module'), 'core');
    $this->assertEntityRefDependency($field_storage, FALSE);
    $field = $this->configFactory->get('field.field.node.article.field_ref_views_select_2429191');
    $this->assertEntityRefDependency($field, FALSE);
    $view = $this->configFactory->get('views.view.entity_reference_plugins_2429191');
    $this->assertEntityRefDependency($view, FALSE);

    // Check that field selection, based on the view, still works. It only
    // selects nodes whose title contains 'foo'.
    $node_1 = Node::create(['type' => 'article', 'title' => 'foobar']);
    $node_1->save();
    $node_2 = Node::create(['type' => 'article', 'title' => 'barbaz']);
    $node_2->save();
    $field = FieldConfig::load('node.article.field_ref_views_select_2429191');
    $selection = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($field);
    $referencable = $selection->getReferenceableEntities();
    $this->assertEqual(array_keys($referencable['article']), [$node_1->id()]);
  }

  /**
   * Tests field_update_8003().
   *
   * @see field_update_8003()
   */
  public function testFieldUpdate8003() {
    // Run updates.
    $this->runUpdates();

    // Check that the new 'auto_create_bundle' setting is populated correctly.
    $field = $this->configFactory->get('field.field.node.article.field_ref_autocreate_2412569');
    $handler_settings = $field->get('settings.handler_settings');

    $expected_target_bundles = ['tags' => 'tags', 'test' => 'test'];
    $this->assertEqual($handler_settings['target_bundles'], $expected_target_bundles);

    $this->assertTrue($handler_settings['auto_create']);
    $this->assertEqual($handler_settings['auto_create_bundle'], 'tags');
  }

  /**
   * Tests field_update_8500().
   *
   * @see field_update_8500()
   */
  public function testFieldUpdate8500() {
    $field_name = 'field_test';
    $field_uuid = '5d0d9870-560b-46c4-b838-0dcded0502dd';
    $field_storage_uuid = 'ce93d7c2-1da7-4a2c-9e6d-b4925e3b129f';

    // Check that we have pre-existing entries for 'field.field.deleted' and
    // 'field.storage.deleted'.
    $deleted_fields = $this->state->get('field.field.deleted');
    $this->assertCount(1, $deleted_fields);
    $this->assertArrayHasKey($field_uuid, $deleted_fields);

    $deleted_field_storages = $this->state->get('field.storage.deleted');
    $this->assertCount(1, $deleted_field_storages);
    $this->assertArrayHasKey($field_storage_uuid, $deleted_field_storages);

    // Ensure that cron does not run automatically after running the updates.
    $this->state->set('system.cron_last', REQUEST_TIME + 100);

    // Run updates.
    $this->runUpdates();

    // Now that we can use the API, check that the "delete fields" state entries
    // have been converted to proper field definition objects.
    $deleted_fields = $this->deletedFieldsRepository->getFieldDefinitions();

    $this->assertCount(1, $deleted_fields);
    $this->assertArrayHasKey($field_uuid, $deleted_fields);
    $this->assertTrue($deleted_fields[$field_uuid] instanceof FieldDefinitionInterface);
    $this->assertEquals($field_name, $deleted_fields[$field_uuid]->getName());

    $deleted_field_storages = $this->deletedFieldsRepository->getFieldStorageDefinitions();
    $this->assertCount(1, $deleted_field_storages);
    $this->assertArrayHasKey($field_storage_uuid, $deleted_field_storages);
    $this->assertTrue($deleted_field_storages[$field_storage_uuid] instanceof FieldStorageDefinitionInterface);
    $this->assertEquals($field_name, $deleted_field_storages[$field_storage_uuid]->getName());

    // Check that the installed storage schema still exists.
    $this->assertNotNull($this->installedStorageSchema->get("node.field_schema_data.$field_name"));

    // Check that the deleted field tables exist.
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = \Drupal::entityTypeManager()->getStorage('node')->getTableMapping();

    $deleted_field_data_table_name = $table_mapping->getDedicatedDataTableName($deleted_field_storages[$field_storage_uuid], TRUE);
    $this->assertTrue($this->database->schema()->tableExists($deleted_field_data_table_name));
    $deleted_field_revision_table_name = $table_mapping->getDedicatedRevisionTableName($deleted_field_storages[$field_storage_uuid], TRUE);
    $this->assertTrue($this->database->schema()->tableExists($deleted_field_revision_table_name));

    // Run cron and repeat the checks above.
    $this->cronRun();

    $deleted_fields = $this->deletedFieldsRepository->getFieldDefinitions();
    $this->assertCount(0, $deleted_fields);

    $deleted_field_storages = $this->deletedFieldsRepository->getFieldStorageDefinitions();
    $this->assertCount(0, $deleted_field_storages);

    // Check that the installed storage schema has been deleted.
    $this->assertNull($this->installedStorageSchema->get("node.field_schema_data.$field_name"));

    // Check that the deleted field tables have been deleted.
    $this->assertFalse($this->database->schema()->tableExists($deleted_field_data_table_name));
    $this->assertFalse($this->database->schema()->tableExists($deleted_field_revision_table_name));
  }

  /**
   * Asserts that a config depends on 'entity_reference' or not
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config to test.
   * @param bool $present
   *   TRUE to test that entity_reference is present, FALSE to test that it is
   *   absent.
   */
  protected function assertEntityRefDependency(Config $config, $present) {
    $dependencies = $config->get('dependencies');
    $dependencies += ['module' => []];
    $this->assertEqual(in_array('entity_reference', $dependencies['module']), $present);
  }

  /**
   * Tests field_post_update_remove_handler_submit_setting().
   *
   * @see field_post_update_remove_handler_submit_setting()
   */
  public function testEntityReferenceFieldConfigCleanUpdate() {
    $field_config = $this->config('field.field.node.article.field_tags');
    // Check that 'handler_submit' key exists in field config settings.
    $this->assertEquals('Change handler', $field_config->get('settings.handler_submit'));

    $this->runUpdates();

    $field_config = $this->config('field.field.node.article.field_tags');
    // Check that 'handler_submit' has been removed from field config settings.
    $this->assertArrayNotHasKey('handler_submit', $field_config->get('settings'));
  }

}
