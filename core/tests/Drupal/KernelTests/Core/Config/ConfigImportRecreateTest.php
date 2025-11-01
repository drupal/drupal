<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ConfigImporterFactory;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests importing recreated configuration entities.
 */
#[Group('config')]
#[RunTestsInSeparateProcesses]
class ConfigImportRecreateTest extends KernelTestBase {

  use ContentTypeCreationTrait;

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
    $this->configImporter = $this->container->get(ConfigImporterFactory::class)->get($storage_comparer->createChangelist());
  }

  /**
   * Tests re-creating a config entity with the same name but different UUID.
   */
  public function testRecreateEntity(): void {
    $type_name = $this->randomMachineName(16);
    $content_type = $this->createContentType([
      'type' => $type_name,
      'name' => 'Node type one',
    ]);
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
    $this->createContentType([
      'type' => $type_name,
      'name' => 'Node type two',
    ]);

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
