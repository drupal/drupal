<?php

namespace Drupal\Tests\config\Kernel;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests importing configuration from files into active configuration.
 *
 * @group config
 */
class ConfigUninstallViaCliImportTest extends KernelTestBase {
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
  public static $modules = ['system', 'config'];

  protected function setUp() {
    parent::setUp();
    if (PHP_SAPI !== 'cli') {
      $this->markTestSkipped('This test has to be run from the CLI');
    }

    $this->installConfig(['system']);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
      $this->container->get('config.storage')
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
   * Tests that the config module can be uninstalled via CLI config import.
   *
   * @see \Drupal\config\ConfigSubscriber
   */
  public function testConfigUninstallViaCli() {
    $this->assertTrue($this->container->get('module_handler')->moduleExists('config'));
    $sync = $this->container->get('config.storage.sync');
    $extensions = $sync->read('core.extension');
    unset($extensions['module']['config']);
    $sync->write('core.extension', $extensions);
    $this->configImporter->reset()->import();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('config'));
  }

}
