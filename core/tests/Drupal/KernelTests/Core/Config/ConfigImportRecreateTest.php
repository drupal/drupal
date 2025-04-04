<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests importing recreated configuration entities.
 *
 * @group config
 */
class ConfigImportRecreateTest extends KernelTestBase {

  /**
   * Config Importer object used for testing.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'field', 'text', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(['system', 'field', 'node']);

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
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme')
    );
  }

  /**
   * Tests re-creating a config entity with the same name but different UUID.
   */
  public function testRecreateEntity(): void {
    $type_name = $this->randomMachineName(16);
    $content_type = NodeType::create([
      'type' => $type_name,
      'name' => 'Node type one',
    ]);
    $content_type->save();
    node_add_body_field($content_type);
    /** @var \Drupal\Core\Config\StorageInterface $active */
    $active = $this->container->get('config.storage');
    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = $this->container->get('config.storage.sync');

    $config_name = $content_type->getEntityType()->getConfigPrefix() . '.' . $content_type->id();
    $this->copyConfig($active, $sync);

    // Delete the content type. This will also delete a field storage, a field,
    // an entity view display and an entity form display.
    $content_type->delete();
    $this->assertFalse($active->exists($config_name), 'Content type\'s old name does not exist active store.');
    // Recreate with the same type - this will have a different UUID.
    $content_type = NodeType::create([
      'type' => $type_name,
      'name' => 'Node type two',
    ]);
    $content_type->save();
    node_add_body_field($content_type);

    $this->configImporter->reset();
    // A node type, a field, an entity view display and an entity form display
    // will be recreated.
    $creates = $this->configImporter->getUnprocessedConfiguration('create');
    $deletes = $this->configImporter->getUnprocessedConfiguration('delete');
    $this->assertCount(5, $creates, 'There are 5 configuration items to create.');
    $this->assertCount(5, $deletes, 'There are 5 configuration items to delete.');
    $this->assertCount(0, $this->configImporter->getUnprocessedConfiguration('update'), 'There are no configuration items to update.');
    $this->assertSame($creates, array_reverse($deletes), 'Deletes and creates contain the same configuration names in opposite orders due to dependencies.');

    $this->configImporter->import();

    // Verify that there is nothing more to import.
    $this->assertFalse($this->configImporter->reset()->hasUnprocessedConfigurationChanges());
    $content_type = NodeType::load($type_name);
    $this->assertEquals('Node type one', $content_type->label());
  }

}
