<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests data type metadata for entity bundles.
 *
 * @group TypedData
 */
class TypedDataDefinitionEntityBundleTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected TypedDataManagerInterface $typedDataManager;

  /**
   * The storage for the 'entity_test_bundle' entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $entityTestBundleStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->typedDataManager = $this->container->get('typed_data_manager');

    $entityTypeManager = $this->container->get('entity_type.manager');
    assert($entityTypeManager instanceof EntityTypeManagerInterface);
    $this->entityTestBundleStorage = $entityTypeManager->getStorage('entity_test_bundle');
  }

  /**
   * Tests that entity bundle definitions are derived correctly.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEntityBundleDefinitions(): void {
    // Start without any bundles and no bundle-specific data type.
    $dataTypes = array_keys($this->typedDataManager->getDefinitions());
    $this->assertContains('entity:entity_test_with_bundle', $dataTypes);
    $this->assertNotContains('entity:entity_test_with_bundle:test', $dataTypes);

    // Add a bundle and make sure the bundle-specific data type is registered.
    $bundle = $this->entityTestBundleStorage->create(['id' => 'test']);
    $bundle->save();
    $dataTypes = array_keys($this->typedDataManager->getDefinitions());
    $this->assertContains('entity:entity_test_with_bundle', $dataTypes);
    $this->assertContains('entity:entity_test_with_bundle:test', $dataTypes);

    // Delete the bundle and make sure the bundle-specific data type is no
    // longer returned.
    $bundle->delete();
    $dataTypes = array_keys($this->typedDataManager->getDefinitions());
    $this->assertContains('entity:entity_test_with_bundle', $dataTypes);
    $this->assertNotContains('entity:entity_test_with_bundle:test', $dataTypes);
  }

}
