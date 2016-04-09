<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests importing configuration which has missing content dependencies.
 *
 * @group config
 */
class ConfigImporterMissingContentTest extends KernelTestBase {

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
  public static $modules = array('system', 'user', 'entity_test', 'config_test', 'config_import_test');

  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(array('config_test'));
    // Installing config_test's default configuration pollutes the global
    // variable being used for recording hook invocations by this test already,
    // so it has to be cleared out manually.
    unset($GLOBALS['hook_config_test']);

    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
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
   * Tests the missing content event is fired.
   *
   * @see \Drupal\Core\Config\ConfigImporter::processMissingContent()
   * @see \Drupal\config_import_test\EventSubscriber
   */
  function testMissingContent() {
    \Drupal::state()->set('config_import_test.config_import_missing_content', TRUE);

    // Update a configuration entity in the sync directory to have a dependency
    // on two content entities that do not exist.
    $storage = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $entity_one = EntityTest::create(array('name' => 'one'));
    $entity_two = EntityTest::create(array('name' => 'two'));
    $entity_three = EntityTest::create(array('name' => 'three'));
    $dynamic_name = 'config_test.dynamic.dotted.default';
    $original_dynamic_data = $storage->read($dynamic_name);
    // Entity one will be resolved by
    // \Drupal\config_import_test\EventSubscriber::onConfigImporterMissingContentOne().
    $original_dynamic_data['dependencies']['content'][] = $entity_one->getConfigDependencyName();
    // Entity two will be resolved by
    // \Drupal\config_import_test\EventSubscriber::onConfigImporterMissingContentTwo().
    $original_dynamic_data['dependencies']['content'][] = $entity_two->getConfigDependencyName();
    // Entity three will be resolved by
    // \Drupal\Core\Config\Importer\FinalMissingContentSubscriber.
    $original_dynamic_data['dependencies']['content'][] = $entity_three->getConfigDependencyName();
    $sync->write($dynamic_name, $original_dynamic_data);

    // Import.
    $this->configImporter->reset()->import();
    $this->assertEqual([], $this->configImporter->getErrors(), 'There were no errors during the import.');
    $this->assertEqual($entity_one->uuid(), \Drupal::state()->get('config_import_test.config_import_missing_content_one'), 'The missing content event is fired during configuration import.');
    $this->assertEqual($entity_two->uuid(), \Drupal::state()->get('config_import_test.config_import_missing_content_two'), 'The missing content event is fired during configuration import.');
    $original_dynamic_data = $storage->read($dynamic_name);
    $this->assertEqual([$entity_one->getConfigDependencyName(), $entity_two->getConfigDependencyName(), $entity_three->getConfigDependencyName()], $original_dynamic_data['dependencies']['content'], 'The imported configuration entity has the missing content entity dependency.');
  }

}
