<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\ContentTranslationConfigImportTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests content translation updates performed during config import.
 *
 * @group content_translation
 */
class ContentTranslationConfigImportTest extends KernelTestBase {

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
  public static $modules = array('system', 'user', 'entity_test', 'language', 'content_translation');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mul');
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
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation')
    );
  }

  /**
   * Tests config import updates.
   */
  function testConfigImportUpdates() {
    $entity_type_id = 'entity_test_mul';
    $config_id = $entity_type_id . '.' . $entity_type_id;
    $config_name = 'language.content_settings.' . $config_id;
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify the configuration to create does not exist yet.
    $this->assertIdentical($storage->exists($config_name), FALSE, $config_name . ' not found.');

    // Create new config entity.
    $data = array(
      'uuid' => 'a019d89b-c4d9-4ed4-b859-894e4e2e93cf',
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => array(
        'module' => array('content_translation')
      ),
      'id' => $config_id,
      'target_entity_type_id' => 'entity_test_mul',
      'target_bundle' => 'entity_test_mul',
      'default_langcode' => 'site_default',
      'language_alterable' => FALSE,
      'third_party_settings' => array(
        'content_translation' => array('enabled' => TRUE),
      ),
    );
    $staging->write($config_name, $data);
    $this->assertIdentical($staging->exists($config_name), TRUE, $config_name . ' found.');

    // Import.
    $this->configImporter->reset()->import();

    // Verify the values appeared.
    $config = $this->config($config_name);
    $this->assertIdentical($config->get('id'), $config_id);

    // Verify that updates were performed.
    $entity_type = $this->container->get('entity.manager')->getDefinition($entity_type_id);
    $table = $entity_type->getDataTable();
    $db_schema = $this->container->get('database')->schema();
    $result = $db_schema->fieldExists($table, 'content_translation_source') && $db_schema->fieldExists($table, 'content_translation_outdated');
    $this->assertTrue($result, 'Content translation updates were successfully performed during config import.');
  }

}
