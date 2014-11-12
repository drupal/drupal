<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigImportRecreateTest.
 */

namespace Drupal\config\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests importing recreated configuration entities.
 *
 * @group config
 */
class ConfigImportRecreateTest extends DrupalUnitTestBase {

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
  public static $modules = array('system', 'entity', 'field', 'text', 'user', 'node', 'entity_reference');

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(array('field'));

    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.staging'));

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

  public function testRecreateEntity() {
    $type_name = Unicode::strtolower($this->randomMachineName(16));
    $content_type = entity_create('node_type', array(
      'type' => $type_name,
      'name' => 'Node type one',
    ));
    $content_type->save();
    /** @var \Drupal\Core\Config\StorageInterface $active */
    $active = $this->container->get('config.storage');
    /** @var \Drupal\Core\Config\StorageInterface $staging */
    $staging = $this->container->get('config.storage.staging');

    $config_name = $content_type->getEntityType()->getConfigPrefix() . '.' . $content_type->id();
    $this->copyConfig($active, $staging);

    // Delete the content type. This will also delete a field storage, a field,
    // an entity view display and an entity form display.
    $content_type->delete();
    $this->assertFalse($active->exists($config_name), 'Content type\'s old name does not exist active store.');
    // Recreate with the same type - this will have a different UUID.
    $content_type = entity_create('node_type', array(
      'type' => $type_name,
      'name' => 'Node type two',
    ));
    $content_type->save();

    $this->configImporter->reset();
    // A node type, a field, an entity view display and an entity form display
    // will be recreated.
    $creates = $this->configImporter->getUnprocessedConfiguration('create');
    $deletes = $this->configImporter->getUnprocessedConfiguration('delete');
    $this->assertEqual(4, count($creates), 'There are 4 configuration items to create.');
    $this->assertEqual(4, count($deletes), 'There are 4 configuration items to delete.');
    $this->assertEqual(0, count($this->configImporter->getUnprocessedConfiguration('update')), 'There are no configuration items to update.');
    $this->assertIdentical($creates, array_reverse($deletes), 'Deletes and creates contain the same configuration names in opposite orders due to dependencies.');

    $this->configImporter->import();

    // Verify that there is nothing more to import.
    $this->assertFalse($this->configImporter->reset()->hasUnprocessedConfigurationChanges());
    $content_type = entity_load('node_type', $type_name);
    $this->assertEqual('Node type one', $content_type->label());
  }

}
