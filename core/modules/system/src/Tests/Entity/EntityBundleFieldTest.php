<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityBundleFieldTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests adding a custom bundle field.
 */
class EntityBundleFieldTest extends EntityUnitTestBase  {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_link');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Entity bundle fields',
      'description' => 'Tests providing a custom bundle field.',
      'group' => 'Entity API',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('user', array('users_data'));
    $this->installSchema('system', array('router'));
    $this->moduleHandler = $this->container->get('module_handler');
    $this->database = $this->container->get('database');
  }

  /**
   * Tests the custom bundle field creation and deletion.
   */
  public function testCustomBundleFieldCreateDelete() {
    // Install the module which adds the field.
    $this->moduleHandler->install(array('entity_bundle_field_test'), FALSE);
    $definition = $this->entityManager->getFieldDefinitions('entity_test', 'custom')['custom_field'];
    $this->assertNotNull($definition, 'Field definition found.');

    // Make sure the table has been created.
    $table = $this->entityManager->getStorage('entity_test')->_fieldTableName($definition);
    $this->assertTrue($this->database->schema()->tableExists($table), 'Table created');
    $this->moduleHandler->uninstall(array('entity_bundle_field_test'), FALSE);
    $this->assertFalse($this->database->schema()->tableExists($table), 'Table dropped');
  }

  /**
   * Tests making use of a custom bundle field.
   */
  public function testCustomBundleFieldUsage() {
    // Check that an entity with bundle entity_test does not have the custom
    // field.
    $this->moduleHandler->install(array('entity_bundle_field_test'), FALSE);
    $storage = $this->entityManager->getStorage('entity_test');
    $entity = $storage->create([
      'type' => 'entity_test',
    ]);
    $this->assertFalse($entity->hasField('custom_field'));

    // Check that the custom bundle has the defined custom field and check
    // saving and deleting of custom field data.
    $entity = $storage->create([
      'type' => 'custom',
    ]);
    $this->assertTrue($entity->hasField('custom_field'));
    $entity->custom_field->value = 'swanky';
    $entity->save();
    $storage->resetCache();
    $entity = $storage->load($entity->id());
    $this->assertEqual($entity->custom_field->value, 'swanky', 'Entity was saved correct.y');

    $entity->custom_field->value = 'cozy';
    $entity->save();
    $storage->resetCache();
    $entity = $storage->load($entity->id());
    $this->assertEqual($entity->custom_field->value, 'cozy', 'Entity was updated correctly.');

    $entity->delete();
    $table = $storage->_fieldTableName($entity->getFieldDefinition('custom_field'));
    $result = $this->database->select($table, 'f')
      ->fields('f')
      ->condition('f.entity_id', $entity->id())
      ->execute();
    $this->assertFalse($result->fetchAssoc(), 'Field data has been deleted');

    // Create another entity to test that values are marked as deleted when a
    // bundle is deleted.
    $entity = $storage->create(['type' => 'custom', 'custom_field' => 'new']);
    $entity->save();
    entity_test_delete_bundle('custom');

    $table = $storage->_fieldTableName($entity->getFieldDefinition('custom_field'));
    $result = $this->database->select($table, 'f')
      ->condition('f.entity_id', $entity->id())
      ->condition('deleted', 1)
      ->countQuery()
      ->execute();
    $this->assertEqual(1, $result->fetchField(), 'Field data has been deleted');

    // @todo Test field purge and table deletion once supported.
    // $this->assertFalse($this->database->schema()->tableExists($table), 'Custom field table was deleted');
  }

}
