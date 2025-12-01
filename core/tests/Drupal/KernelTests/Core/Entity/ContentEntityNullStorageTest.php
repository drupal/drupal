<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestNoIdBundle;
use Drupal\Core\Config\ConfigImporterFactory;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ContentEntityNullStorage entity query support.
 *
 * @see \Drupal\Core\Entity\ContentEntityNullStorage
 * @see \Drupal\Core\Entity\Query\Null\Query
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class ContentEntityNullStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'entity_test', 'user'];

  /**
   * Tests using entity query with ContentEntityNullStorage.
   *
   * @see \Drupal\Core\Entity\Query\Null\Query
   */
  public function testEntityQuery(): void {
    $this->assertSame(0, \Drupal::entityQuery('entity_test_no_id')->accessCheck(FALSE)->count()->execute(), 'Counting a null storage returns 0.');
    $this->assertSame([], \Drupal::entityQuery('entity_test_no_id')->accessCheck(FALSE)->execute(), 'Querying a null storage returns an empty array.');
    $this->assertSame([], \Drupal::entityQuery('entity_test_no_id')->accessCheck(FALSE)->condition('type', 'test')->execute(), 'Querying a null storage returns an empty array and conditions are ignored.');
    $this->assertSame([], \Drupal::entityQueryAggregate('entity_test_no_id')->accessCheck(FALSE)->aggregate('name', 'AVG')->execute(), 'Aggregate querying a null storage returns an empty array');

  }

  /**
   * Tests deleting an entity test no ID bundle entity via a configuration import.
   *
   * @see \Drupal\Core\Entity\Event\BundleConfigImportValidate
   */
  public function testDeleteThroughImport(): void {
    $this->installConfig(['system']);
    $entity_test_no_id_bundle = EntityTestNoIdBundle::create([
      'id' => 'test',
      'label' => 'Test entity test no ID bundle',
    ]);
    $entity_test_no_id_bundle->save();

    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
      $this->container->get('config.storage')
    );
    $config_importer = $this->container->get(ConfigImporterFactory::class)->get($storage_comparer->createChangelist());

    // Delete the entity test no ID bundle in sync.
    $sync = $this->container->get('config.storage.sync');
    $sync->delete($entity_test_no_id_bundle->getConfigDependencyName());

    // Import.
    $config_importer->reset()->import();
    $this->assertNull(EntityTestNoIdBundle::load($entity_test_no_id_bundle->id()), 'The entity test no ID bundle has been deleted.');
  }

}
