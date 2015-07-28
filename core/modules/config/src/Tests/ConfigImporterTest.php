<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigImporterTest.
 */

namespace Drupal\config\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\StorageComparer;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests importing configuration from files into active configuration.
 *
 * @group config
 */
class ConfigImporterTest extends KernelTestBase {

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
  public static $modules = array('config_test', 'system', 'config_import_test');

  protected function setUp() {
    parent::setUp();

    $this->installConfig(array('config_test'));
    // Installing config_test's default configuration pollutes the global
    // variable being used for recording hook invocations by this test already,
    // so it has to be cleared out manually.
    unset($GLOBALS['hook_config_test']);

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
   * Tests omission of module APIs for bare configuration operations.
   */
  function testNoImport() {
    $dynamic_name = 'config_test.dynamic.dotted.default';

    // Verify the default configuration values exist.
    $config = $this->config($dynamic_name);
    $this->assertIdentical($config->get('id'), 'dotted.default');

    // Verify that a bare $this->config() does not involve module APIs.
    $this->assertFalse(isset($GLOBALS['hook_config_test']));
  }

  /**
   * Tests that trying to import from an empty staging configuration directory
   * fails.
   */
  function testEmptyImportFails() {
    try {
      $this->container->get('config.storage.staging')->deleteAll();
      $this->configImporter->reset()->import();
      $this->fail('ConfigImporterException thrown, successfully stopping an empty import.');
    }
    catch (ConfigImporterException $e) {
      $this->pass('ConfigImporterException thrown, successfully stopping an empty import.');
    }
  }

  /**
   * Tests verification of site UUID before importing configuration.
   */
  function testSiteUuidValidate() {
    $staging = \Drupal::service('config.storage.staging');
    // Create updated configuration object.
    $config_data = $this->config('system.site')->get();
    // Generate a new site UUID.
    $config_data['uuid'] = \Drupal::service('uuid')->generate();
    $staging->write('system.site', $config_data);
    try {
      $this->configImporter->reset()->import();
      $this->fail('ConfigImporterException not thrown, invalid import was not stopped due to mis-matching site UUID.');
    }
    catch (ConfigImporterException $e) {
      $this->assertEqual($e->getMessage(), 'There were errors validating the config synchronization.');
      $error_log = $this->configImporter->getErrors();
      $expected = array('Site UUID in source storage does not match the target storage.');
      $this->assertEqual($expected, $error_log);
    }
  }

  /**
   * Tests deletion of configuration during import.
   */
  function testDeleted() {
    $dynamic_name = 'config_test.dynamic.dotted.default';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify the default configuration values exist.
    $config = $this->config($dynamic_name);
    $this->assertIdentical($config->get('id'), 'dotted.default');

    // Delete the file from the staging directory.
    $staging->delete($dynamic_name);

    // Import.
    $this->configImporter->reset()->import();

    // Verify the file has been removed.
    $this->assertIdentical($storage->read($dynamic_name), FALSE);

    $config = $this->config($dynamic_name);
    $this->assertIdentical($config->get('id'), NULL);

    // Verify that appropriate module API hooks have been invoked.
    $this->assertTrue(isset($GLOBALS['hook_config_test']['load']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['delete']));

    $this->assertFalse($this->configImporter->hasUnprocessedConfigurationChanges());
    $logs = $this->configImporter->getErrors();
    $this->assertEqual(count($logs), 0);
  }

  /**
   * Tests creation of configuration during import.
   */
  function testNew() {
    $dynamic_name = 'config_test.dynamic.new';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify the configuration to create does not exist yet.
    $this->assertIdentical($storage->exists($dynamic_name), FALSE, $dynamic_name . ' not found.');

    // Create new config entity.
    $original_dynamic_data = array(
      'uuid' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'status' => TRUE,
      'dependencies' => array(),
      'id' => 'new',
      'label' => 'New',
      'weight' => 0,
      'style' => '',
      'size' => '',
      'size_value' => '',
      'protected_property' => '',
    );
    $staging->write($dynamic_name, $original_dynamic_data);

    $this->assertIdentical($staging->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Import.
    $this->configImporter->reset()->import();

    // Verify the values appeared.
    $config = $this->config($dynamic_name);
    $this->assertIdentical($config->get('label'), $original_dynamic_data['label']);

    // Verify that appropriate module API hooks have been invoked.
    $this->assertFalse(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));

    // Verify that hook_config_import_steps_alter() can add steps to
    // configuration synchronization.
    $this->assertTrue(isset($GLOBALS['hook_config_test']['config_import_steps_alter']));

    // Verify that there is nothing more to import.
    $this->assertFalse($this->configImporter->hasUnprocessedConfigurationChanges());
    $logs = $this->configImporter->getErrors();
    $this->assertEqual(count($logs), 0);
  }

  /**
   * Tests that secondary writes are overwritten.
   */
  function testSecondaryWritePrimaryFirst() {
    $name_primary = 'config_test.dynamic.primary';
    $name_secondary = 'config_test.dynamic.secondary';
    $staging = $this->container->get('config.storage.staging');
    $uuid = $this->container->get('uuid');

    $values_primary = array(
      'id' => 'primary',
      'label' => 'Primary',
      'weight' => 0,
      'uuid' => $uuid->generate(),
    );
    $staging->write($name_primary, $values_primary);
    $values_secondary = array(
      'id' => 'secondary',
      'label' => 'Secondary Sync',
      'weight' => 0,
      'uuid' => $uuid->generate(),
      // Add a dependency on primary, to ensure that is synced first.
      'dependencies' => array(
        'config' => array($name_primary),
      )
    );
    $staging->write($name_secondary, $values_secondary);

    // Import.
    $this->configImporter->reset()->import();

    $entity_storage = \Drupal::entityManager()->getStorage('config_test');
    $primary = $entity_storage->load('primary');
    $this->assertEqual($primary->id(), 'primary');
    $this->assertEqual($primary->uuid(), $values_primary['uuid']);
    $this->assertEqual($primary->label(), $values_primary['label']);
    $secondary = $entity_storage->load('secondary');
    $this->assertEqual($secondary->id(), 'secondary');
    $this->assertEqual($secondary->uuid(), $values_secondary['uuid']);
    $this->assertEqual($secondary->label(), $values_secondary['label']);

    $logs = $this->configImporter->getErrors();
    $this->assertEqual(count($logs), 1);
    $this->assertEqual($logs[0], SafeMarkup::format('Deleted and replaced configuration entity "@name"', array('@name' => $name_secondary)));
  }

  /**
   * Tests that secondary writes are overwritten.
   */
  function testSecondaryWriteSecondaryFirst() {
    $name_primary = 'config_test.dynamic.primary';
    $name_secondary = 'config_test.dynamic.secondary';
    $staging = $this->container->get('config.storage.staging');
    $uuid = $this->container->get('uuid');

    $values_primary = array(
      'id' => 'primary',
      'label' => 'Primary',
      'weight' => 0,
      'uuid' => $uuid->generate(),
      // Add a dependency on secondary, so that is synced first.
      'dependencies' => array(
        'config' => array($name_secondary),
      )
    );
    $staging->write($name_primary, $values_primary);
    $values_secondary = array(
      'id' => 'secondary',
      'label' => 'Secondary Sync',
      'weight' => 0,
      'uuid' => $uuid->generate(),
    );
    $staging->write($name_secondary, $values_secondary);

    // Import.
    $this->configImporter->reset()->import();

    $entity_storage = \Drupal::entityManager()->getStorage('config_test');
    $primary = $entity_storage->load('primary');
    $this->assertEqual($primary->id(), 'primary');
    $this->assertEqual($primary->uuid(), $values_primary['uuid']);
    $this->assertEqual($primary->label(), $values_primary['label']);
    $secondary = $entity_storage->load('secondary');
    $this->assertEqual($secondary->id(), 'secondary');
    $this->assertEqual($secondary->uuid(), $values_secondary['uuid']);
    $this->assertEqual($secondary->label(), $values_secondary['label']);

    $logs = $this->configImporter->getErrors();
    $this->assertEqual(count($logs), 1);
    $message = SafeMarkup::format("'config_test' entity with ID '@name' already exists", array('@name' => 'secondary'));
    $this->assertEqual($logs[0], SafeMarkup::format('Unexpected error during import with operation @op for @name: !message.', array('@op' => 'create', '@name' => $name_primary, '!message' => $message)));
  }

  /**
   * Tests that secondary updates for deleted files work as expected.
   */
  function testSecondaryUpdateDeletedDeleterFirst() {
    $name_deleter = 'config_test.dynamic.deleter';
    $name_deletee = 'config_test.dynamic.deletee';
    $name_other = 'config_test.dynamic.other';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $uuid = $this->container->get('uuid');

    $values_deleter = array(
      'id' => 'deleter',
      'label' => 'Deleter',
      'weight' => 0,
      'uuid' => $uuid->generate(),
    );
    $storage->write($name_deleter, $values_deleter);
    $values_deleter['label'] = 'Updated Deleter';
    $staging->write($name_deleter, $values_deleter);
    $values_deletee = array(
      'id' => 'deletee',
      'label' => 'Deletee',
      'weight' => 0,
      'uuid' => $uuid->generate(),
      // Add a dependency on deleter, to make sure that is synced first.
      'dependencies' => array(
        'config' => array($name_deleter),
      )
    );
    $storage->write($name_deletee, $values_deletee);
    $values_deletee['label'] = 'Updated Deletee';
    $staging->write($name_deletee, $values_deletee);

    // Ensure that import will continue after the error.
    $values_other = array(
      'id' => 'other',
      'label' => 'Other',
      'weight' => 0,
      'uuid' => $uuid->generate(),
      // Add a dependency on deleter, to make sure that is synced first. This
      // will also be synced after the deletee due to alphabetical ordering.
      'dependencies' => array(
        'config' => array($name_deleter),
      )
    );
    $storage->write($name_other, $values_other);
    $values_other['label'] = 'Updated other';
    $staging->write($name_other, $values_other);

    // Check update changelist order.
    $updates = $this->configImporter->reset()->getStorageComparer()->getChangelist('update');
    $expected = array(
      $name_deleter,
      $name_deletee,
      $name_other,
    );
    $this->assertIdentical($expected, $updates);

    // Import.
    $this->configImporter->import();

    $entity_storage = \Drupal::entityManager()->getStorage('config_test');
    $deleter = $entity_storage->load('deleter');
    $this->assertEqual($deleter->id(), 'deleter');
    $this->assertEqual($deleter->uuid(), $values_deleter['uuid']);
    $this->assertEqual($deleter->label(), $values_deleter['label']);

    // The deletee was deleted in
    // \Drupal\config_test\Entity\ConfigTest::postSave().
    $this->assertFalse($entity_storage->load('deletee'));

    $other = $entity_storage->load('other');
    $this->assertEqual($other->id(), 'other');
    $this->assertEqual($other->uuid(), $values_other['uuid']);
    $this->assertEqual($other->label(), $values_other['label']);

    $logs = $this->configImporter->getErrors();
    $this->assertEqual(count($logs), 1);
    $this->assertEqual($logs[0], SafeMarkup::format('Update target "@name" is missing.', array('@name' => $name_deletee)));
  }

  /**
   * Tests that secondary updates for deleted files work as expected.
   *
   * This test is completely hypothetical since we only support full
   * configuration tree imports. Therefore, any configuration updates that cause
   * secondary deletes should be reflected already in the staged configuration.
   */
  function testSecondaryUpdateDeletedDeleteeFirst() {
    $name_deleter = 'config_test.dynamic.deleter';
    $name_deletee = 'config_test.dynamic.deletee';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $uuid = $this->container->get('uuid');

    $values_deleter = array(
      'id' => 'deleter',
      'label' => 'Deleter',
      'weight' => 0,
      'uuid' => $uuid->generate(),
      // Add a dependency on deletee, to make sure that is synced first.
      'dependencies' => array(
        'config' => array($name_deletee),
      ),
    );
    $storage->write($name_deleter, $values_deleter);
    $values_deleter['label'] = 'Updated Deleter';
    $staging->write($name_deleter, $values_deleter);
    $values_deletee = array(
      'id' => 'deletee',
      'label' => 'Deletee',
      'weight' => 0,
      'uuid' => $uuid->generate(),
    );
    $storage->write($name_deletee, $values_deletee);
    $values_deletee['label'] = 'Updated Deletee';
    $staging->write($name_deletee, $values_deletee);

    // Import.
    $this->configImporter->reset()->import();

    $entity_storage = \Drupal::entityManager()->getStorage('config_test');
    // Both entities are deleted. ConfigTest::postSave() causes updates of the
    // deleter entity to delete the deletee entity. Since the deleter depends on
    // the deletee, removing the deletee causes the deleter to be removed.
    $this->assertFalse($entity_storage->load('deleter'));
    $this->assertFalse($entity_storage->load('deletee'));
    $logs = $this->configImporter->getErrors();
    $this->assertEqual(count($logs), 0);
  }

  /**
   * Tests that secondary deletes for deleted files work as expected.
   */
  function testSecondaryDeletedDeleteeSecond() {
    $name_deleter = 'config_test.dynamic.deleter';
    $name_deletee = 'config_test.dynamic.deletee';
    $storage = $this->container->get('config.storage');

    $uuid = $this->container->get('uuid');

    $values_deleter = array(
      'id' => 'deleter',
      'label' => 'Deleter',
      'weight' => 0,
      'uuid' => $uuid->generate(),
      // Add a dependency on deletee, to make sure this delete is synced first.
      'dependencies' => array(
        'config' => array($name_deletee),
      ),
    );
    $storage->write($name_deleter, $values_deleter);
    $values_deletee = array(
      'id' => 'deletee',
      'label' => 'Deletee',
      'weight' => 0,
      'uuid' => $uuid->generate(),
    );
    $storage->write($name_deletee, $values_deletee);

    // Import.
    $this->configImporter->reset()->import();

    $entity_storage = \Drupal::entityManager()->getStorage('config_test');
    $this->assertFalse($entity_storage->load('deleter'));
    $this->assertFalse($entity_storage->load('deletee'));
    // The deletee entity does not exist as the delete worked and although the
    // delete occurred in \Drupal\config_test\Entity\ConfigTest::postDelete()
    // this does not matter.
    $logs = $this->configImporter->getErrors();
    $this->assertEqual(count($logs), 0);
  }

  /**
   * Tests updating of configuration during import.
   */
  function testUpdated() {
    $name = 'config_test.system';
    $dynamic_name = 'config_test.dynamic.dotted.default';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify that the configuration objects to import exist.
    $this->assertIdentical($storage->exists($name), TRUE, $name . ' found.');
    $this->assertIdentical($storage->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Replace the file content of the existing configuration objects in the
    // staging directory.
    $original_name_data = array(
      'foo' => 'beer',
    );
    $staging->write($name, $original_name_data);
    $original_dynamic_data = $storage->read($dynamic_name);
    $original_dynamic_data['label'] = 'Updated';
    $staging->write($dynamic_name, $original_dynamic_data);

    // Verify the active configuration still returns the default values.
    $config = $this->config($name);
    $this->assertIdentical($config->get('foo'), 'bar');
    $config = $this->config($dynamic_name);
    $this->assertIdentical($config->get('label'), 'Default');

    // Import.
    $this->configImporter->reset()->import();

    // Verify the values were updated.
    \Drupal::configFactory()->reset($name);
    $config = $this->config($name);
    $this->assertIdentical($config->get('foo'), 'beer');
    $config = $this->config($dynamic_name);
    $this->assertIdentical($config->get('label'), 'Updated');

    // Verify that the original file content is still the same.
    $this->assertIdentical($staging->read($name), $original_name_data);
    $this->assertIdentical($staging->read($dynamic_name), $original_dynamic_data);

    // Verify that appropriate module API hooks have been invoked.
    $this->assertTrue(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));

    // Verify that there is nothing more to import.
    $this->assertFalse($this->configImporter->hasUnprocessedConfigurationChanges());
    $logs = $this->configImporter->getErrors();
    $this->assertEqual(count($logs), 0);
  }

  /**
   * Tests the isInstallable method()
   */
  function testIsInstallable() {
    $config_name = 'config_test.dynamic.isinstallable';
    $this->assertFalse($this->container->get('config.storage')->exists($config_name));
    \Drupal::state()->set('config_test.isinstallable', TRUE);
    $this->installConfig(array('config_test'));
    $this->assertTrue($this->container->get('config.storage')->exists($config_name));
  }

  /**
   * Tests dependency validation during configuration import.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   * @see \Drupal\Core\Config\ConfigImporter::createExtensionChangelist()
   */
  public function testUnmetDependency() {
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Test an unknown configuration owner.
    $staging->write('unknown.config', ['test' => 'test']);

    // Make a config entity have unmet dependencies.
    $config_entity_data = $staging->read('config_test.dynamic.dotted.default');
    $config_entity_data['dependencies'] = ['module' => ['unknown']];
    $staging->write('config_test.dynamic.dotted.module', $config_entity_data);
    $config_entity_data['dependencies'] = ['theme' => ['unknown']];
    $staging->write('config_test.dynamic.dotted.theme', $config_entity_data);
    $config_entity_data['dependencies'] = ['config' => ['unknown']];
    $staging->write('config_test.dynamic.dotted.config', $config_entity_data);

    // Make an active config depend on something that is missing in staging.
    // The whole configuration needs to be consistent, not only the updated one.
    $config_entity_data['dependencies'] = [];
    $storage->write('config_test.dynamic.dotted.deleted', $config_entity_data);
    $config_entity_data['dependencies'] = ['config' => ['config_test.dynamic.dotted.deleted']];
    $storage->write('config_test.dynamic.dotted.existing', $config_entity_data);
    $staging->write('config_test.dynamic.dotted.existing', $config_entity_data);

    $extensions = $staging->read('core.extension');
    // Add a module and a theme that do not exist.
    $extensions['module']['unknown_module'] = 0;
    $extensions['theme']['unknown_theme'] = 0;
    // Add a module and a theme that depend on uninstalled extensions.
    $extensions['module']['book'] = 0;
    $extensions['theme']['bartik'] = 0;

    $staging->write('core.extension', $extensions);
    try {
      $this->configImporter->reset()->import();
      $this->fail('ConfigImporterException not thrown; an invalid import was not stopped due to missing dependencies.');
    }
    catch (ConfigImporterException $e) {
      $this->assertEqual($e->getMessage(), 'There were errors validating the config synchronization.');
      $error_log = $this->configImporter->getErrors();
      $expected = [
        'Unable to install the <em class="placeholder">unknown_module</em> module since it does not exist.',
        'Unable to install the <em class="placeholder">Book</em> module since it requires the <em class="placeholder">Node, Text, Field, Filter, User, Entity Reference</em> modules.',
        'Unable to install the <em class="placeholder">unknown_theme</em> theme since it does not exist.',
        'Unable to install the <em class="placeholder">Bartik</em> theme since it requires the <em class="placeholder">Classy</em> theme.',
        'Configuration <em class="placeholder">config_test.dynamic.dotted.config</em> depends on the <em class="placeholder">unknown</em> configuration that will not exist after import.',
        'Configuration <em class="placeholder">config_test.dynamic.dotted.existing</em> depends on the <em class="placeholder">config_test.dynamic.dotted.deleted</em> configuration that will not exist after import.',
        'Configuration <em class="placeholder">config_test.dynamic.dotted.module</em> depends on the <em class="placeholder">unknown</em> module that will not be installed after import.',
        'Configuration <em class="placeholder">config_test.dynamic.dotted.theme</em> depends on the <em class="placeholder">unknown</em> theme that will not be installed after import.',
        'Configuration <em class="placeholder">unknown.config</em> depends on the <em class="placeholder">unknown</em> extension that will not be installed after import.',
      ];
      foreach ($expected as $expected_message) {
        $this->assertTrue(in_array($expected_message, $error_log), $expected_message);
      }
    }

    // Make a config entity have mulitple unmet dependencies.
    $config_entity_data = $staging->read('config_test.dynamic.dotted.default');
    $config_entity_data['dependencies'] = ['module' => ['unknown', 'dblog']];
    $staging->write('config_test.dynamic.dotted.module', $config_entity_data);
    $config_entity_data['dependencies'] = ['theme' => ['unknown', 'seven']];
    $staging->write('config_test.dynamic.dotted.theme', $config_entity_data);
    $config_entity_data['dependencies'] = ['config' => ['unknown', 'unknown2']];
    $staging->write('config_test.dynamic.dotted.config', $config_entity_data);
    try {
      $this->configImporter->reset()->import();
      $this->fail('ConfigImporterException not thrown, invalid import was not stopped due to missing dependencies.');
    }
    catch (ConfigImporterException $e) {
      $this->assertEqual($e->getMessage(), 'There were errors validating the config synchronization.');
      $error_log = $this->configImporter->getErrors();
      $expected = [
        'Configuration <em class="placeholder">config_test.dynamic.dotted.config</em> depends on configuration (<em class="placeholder">unknown, unknown2</em>) that will not exist after import.',
        'Configuration <em class="placeholder">config_test.dynamic.dotted.module</em> depends on modules (<em class="placeholder">unknown, Database Logging</em>) that will not be installed after import.',
        'Configuration <em class="placeholder">config_test.dynamic.dotted.theme</em> depends on themes (<em class="placeholder">unknown, Seven</em>) that will not be installed after import.',
      ];
      foreach ($expected as $expected_message) {
        $this->assertTrue(in_array($expected_message, $error_log), $expected_message);
      }
    }
  }

  /**
   * Tests missing core.extension during configuration import.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   */
  public function testMissingCoreExtension() {
    $staging = $this->container->get('config.storage.staging');
    $staging->delete('core.extension');
    try {
      $this->configImporter->reset()->import();
      $this->fail('ConfigImporterException not thrown, invalid import was not stopped due to missing dependencies.');
    }
    catch (ConfigImporterException $e) {
      $this->assertEqual($e->getMessage(), 'There were errors validating the config synchronization.');
      $error_log = $this->configImporter->getErrors();
      $this->assertEqual(['The core.extension configuration does not exist.'], $error_log);
    }
  }

  /**
   * Tests install profile validation during configuration import.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   */
  public function testInstallProfile() {
    $staging = $this->container->get('config.storage.staging');

    $extensions = $staging->read('core.extension');
    // Add an install profile.
    $extensions['module']['standard'] = 0;

    $staging->write('core.extension', $extensions);
    try {
      $this->configImporter->reset()->import();
      $this->fail('ConfigImporterException not thrown; an invalid import was not stopped due to missing dependencies.');
    }
    catch (ConfigImporterException $e) {
      $this->assertEqual($e->getMessage(), 'There were errors validating the config synchronization.');
      $error_log = $this->configImporter->getErrors();
      // Install profiles should not even be scanned at this point.
      $this->assertEqual(['Unable to install the <em class="placeholder">standard</em> module since it does not exist.'], $error_log);
    }
  }

}
