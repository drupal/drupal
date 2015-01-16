<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigInstallTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\StorageInterface;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests installation of configuration objects in installation functionality.
 *
 * @group config
 * @see \Drupal\Core\Config\ConfigInstaller
 */
class ConfigInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Ensure the global variable being asserted by this test does not exist;
    // a previous test executed in this request/process might have set it.
    unset($GLOBALS['hook_config_test']);
  }

  /**
   * Tests module installation.
   */
  function testModuleInstallation() {
    $default_config = 'config_test.system';
    $default_configuration_entity = 'config_test.dynamic.dotted.default';

    // Verify that default module config does not exist before installation yet.
    $config = $this->config($default_config);
    $this->assertIdentical($config->isNew(), TRUE);
    $config = $this->config($default_configuration_entity);
    $this->assertIdentical($config->isNew(), TRUE);

    // Ensure that schema provided by modules that are not installed is not
    // available.
    $this->assertFalse(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.schema_in_install'), 'Configuration schema for config_schema_test.schema_in_install does not exist.');

    // Install the test module.
    $this->enableModules(array('config_test', 'config_schema_test'));
    $this->installConfig(array('config_test', 'config_schema_test'));

    // After module installation the new schema should exist.
    $this->assertTrue(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.schema_in_install'), 'Configuration schema for config_schema_test.schema_in_install exists.');

    // Verify that default module config exists.
    \Drupal::configFactory()->reset($default_config);
    \Drupal::configFactory()->reset($default_configuration_entity);
    $config = $this->config($default_config);
    $this->assertIdentical($config->isNew(), FALSE);
    $config = $this->config($default_configuration_entity);
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify that config_test API hooks were invoked for the dynamic default
    // configuration entity.
    $this->assertFalse(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));

    // Ensure that data type casting is applied during config installation.
    $config = $this->config('config_schema_test.schema_in_install');
    $this->assertIdentical($config->get('integer'), 1);

    // Test that uninstalling configuration removes configuration schema.
    $this->config('core.extension')->set('module', array())->save();
    \Drupal::service('config.manager')->uninstall('module', 'config_test');
    $this->assertFalse(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.schema_in_install'), 'Configuration schema for config_schema_test.schema_in_install does not exist.');
  }

  /**
   * Tests that collections are ignored if the event does not return anything.
   */
  public function testCollectionInstallationNoCollections() {
    // Install the test module.
    $this->enableModules(array('config_collection_install_test'));
    $this->installConfig(array('config_collection_install_test'));
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEqual(array(), $active_storage->getAllCollectionNames());
  }

  /**
   * Tests config objects in collections are installed as expected.
   */
  public function testCollectionInstallationCollections() {
    $collections = array(
      'another_collection',
      'collection.test1',
      'collection.test2',
    );
    // Set the event listener to return three possible collections.
    // @see \Drupal\config_collection_install_test\EventSubscriber
    \Drupal::state()->set('config_collection_install_test.collection_names', $collections);
    // Install the test module.
    $this->enableModules(array('config_collection_install_test'));
    $this->installConfig(array('config_collection_install_test'));
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEqual($collections, $active_storage->getAllCollectionNames());
    foreach ($collections as $collection) {
      $collection_storage = $active_storage->createCollection($collection);
      $data = $collection_storage->read('config_collection_install_test.test');
      $this->assertEqual($collection, $data['collection']);
    }

    // Tests that clashing configuration in collections is detected.
    try {
      \Drupal::service('module_installer')->install(['config_collection_clash_install_test']);
      $this->fail('Expected PreExistingConfigException not thrown.');
    }
    catch (PreExistingConfigException $e) {
      $this->assertEqual($e->getExtension(), 'config_collection_clash_install_test');
      $this->assertEqual($e->getConfigObjects(), [
        'another_collection' => ['config_collection_install_test.test'],
        'collection.test1' => ['config_collection_install_test.test'],
        'collection.test2' => ['config_collection_install_test.test'],
      ]);
      $this->assertEqual($e->getMessage(), 'Configuration objects (another_collection/config_collection_install_test.test, collection/test1/config_collection_install_test.test, collection/test2/config_collection_install_test.test) provided by config_collection_clash_install_test already exist in active configuration');
    }

    // Test that the we can use the config installer to install all the
    // available default configuration in a particular collection for enabled
    // extensions.
    \Drupal::service('config.installer')->installCollectionDefaultConfig('entity');
    // The 'entity' collection will not exist because the 'config_test' module
    // is not enabled.
    $this->assertEqual($collections, $active_storage->getAllCollectionNames());
    // Enable the 'config_test' module and try again.
    $this->enableModules(array('config_test'));
    \Drupal::service('config.installer')->installCollectionDefaultConfig('entity');
    $collections[] = 'entity';
    $this->assertEqual($collections, $active_storage->getAllCollectionNames());
    $collection_storage = $active_storage->createCollection('entity');
    $data = $collection_storage->read('config_test.dynamic.dotted.default');
    $this->assertIdentical(array('label' => 'entity'), $data);

    // Test that the config manager uninstalls configuration from collections
    // as expected.
    \Drupal::service('config.manager')->uninstall('module', 'config_collection_install_test');
    $this->assertEqual(array('entity'), $active_storage->getAllCollectionNames());
    \Drupal::service('config.manager')->uninstall('module', 'config_test');
    $this->assertEqual(array(), $active_storage->getAllCollectionNames());
  }

  /**
   * Tests collections which do not support config entities install correctly.
   *
   * Config entity detection during config installation is done by matching
   * config name prefixes. If a collection provides a configuration with a
   * matching name but does not support config entities it should be created
   * using simple configuration.
   */
  public function testCollectionInstallationCollectionConfigEntity() {
    $collections = array(
      'entity',
    );
    \Drupal::state()->set('config_collection_install_test.collection_names', $collections);
    // Install the test module.
    $this->enableModules(array('config_test', 'config_collection_install_test'));
    $this->installConfig(array('config_test'));
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEqual($collections, $active_storage->getAllCollectionNames());
    $collection_storage = $active_storage->createCollection('entity');

    // The config_test.dynamic.dotted.default configuraton object saved in the
    // active store should be a configuration entity complete with UUID. Because
    // the entity collection does not support configuration entities the
    // configuration object stored there with the same name should only contain
    // a label.
    $name = 'config_test.dynamic.dotted.default';
    $data = $active_storage->read($name);
    $this->assertTrue(isset($data['uuid']));
    $data = $collection_storage->read($name);
    $this->assertIdentical(array('label' => 'entity'), $data);
  }

}
