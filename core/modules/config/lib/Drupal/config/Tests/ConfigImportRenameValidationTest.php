<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigImportRenameValidationTest.
 */

namespace Drupal\config\Tests;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\StorageComparer;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests validating renamed configuration in a configuration import.
 */
class ConfigImportRenameValidationTest extends DrupalUnitTestBase {

  /**
   * Config Importer object used for testing.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'node', 'field', 'text', 'entity', 'config_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Configuration import rename validation',
      'description' => 'Tests validating renamed configuration in a configuration import.',
      'group' => 'Configuration',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node');

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.staging'),
      $this->container->get('config.storage'),
      $this->container->get('config.manager')
    );
    $this->configImporter = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation')
    );
  }

  /**
   * Tests configuration renaming validation.
   */
  public function testRenameValidation() {
    // Create a test entity.
    $test_entity_id = $this->randomName();
    $test_entity = entity_create('config_test', array(
      'id' => $test_entity_id,
      'label' => $this->randomName(),
    ));
    $test_entity->save();
    $uuid = $test_entity->uuid();

    // Stage the test entity and then delete it from the active storage.
    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);
    $test_entity->delete();

    // Create a content type with a matching UUID in the active storage.
    $content_type = entity_create('node_type', array(
      'type' => Unicode::strtolower($this->randomName(16)),
      'name' => $this->randomName(),
      'uuid' => $uuid,
    ));
    $content_type->save();

    // Confirm that the staged configuration is detected as a rename since the
    // UUIDs match.
    $this->configImporter->reset();
    $expected = array(
      'node.type.' . $content_type->id() . '::config_test.dynamic.' . $test_entity_id,
    );
    $renames = $this->configImporter->getUnprocessedConfiguration('rename');
    $this->assertIdentical($expected, $renames);

    // Try to import the configuration. We expect an exception to be thrown
    // because the staged entity is of a different type.
    try {
      $this->configImporter->import();
      $this->fail('Expected ConfigImporterException thrown when a renamed configuration entity does not match the existing entity type.');
    }
    catch (ConfigImporterException $e) {
      $this->pass('Expected ConfigImporterException thrown when a renamed configuration entity does not match the existing entity type.');
      $expected = array(
        String::format('Entity type mismatch on rename. !old_type not equal to !new_type for existing configuration !old_name and staged configuration !new_name.', array('old_type' => 'node_type', 'new_type' => 'config_test', 'old_name' => 'node.type.' . $content_type->id(), 'new_name' => 'config_test.dynamic.' . $test_entity_id))
      );
      $this->assertIdentical($expected, $this->configImporter->getErrors());
    }
  }

  /**
   * Tests configuration renaming validation for simple configuration.
   */
  public function testRenameSimpleConfigValidation() {
    $uuid = new Php();
    // Create a simple configuration with a UUID.
    $config = \Drupal::config('config_test.new');
    $uuid_value = $uuid->generate();
    $config->set('uuid', $uuid_value)->save();

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);
    $config->delete();

    // Create another simple configuration with the same UUID.
    $config = \Drupal::config('config_test.old');
    $config->set('uuid', $uuid_value)->save();

    // Confirm that the staged configuration is detected as a rename since the
    // UUIDs match.
    $this->configImporter->reset();
    $expected = array(
      'config_test.old::config_test.new'
    );
    $renames = $this->configImporter->getUnprocessedConfiguration('rename');
    $this->assertIdentical($expected, $renames);

    // Try to import the configuration. We expect an exception to be thrown
    // because the rename is for simple configuration.
    try {
      $this->configImporter->import();
      $this->fail('Expected ConfigImporterException thrown when simple configuration is renamed.');
    }
    catch (ConfigImporterException $e) {
      $this->pass('Expected ConfigImporterException thrown when simple configuration is renamed.');
      $expected = array(
        String::format('Rename operation for simple configuration. Existing configuration !old_name and staged configuration !new_name.', array('old_name' => 'config_test.old', 'new_name' => 'config_test.new'))
      );
      $this->assertIdentical($expected, $this->configImporter->getErrors());
    }
  }

}
