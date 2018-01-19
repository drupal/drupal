<?php

namespace Drupal\Tests\demo_umami_content\Functional;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that uninstalling default content removes created content.
 *
 * @group demo_umami_content
 */
class UninstallDefaultContentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Tests uninstalling content removes created entities.
   */
  public function testReinstall() {
    $module_installer = $this->container->get('module_installer');

    // Test imported blocks on profile install.
    $block_storage = $this->container->get('entity_type.manager')->getStorage('block_content');
    $this->assertImportedCustomBlock($block_storage);

    // Test imported nodes on profile install.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->assertRecipesImported($node_storage);

    $count = $node_storage->getQuery()
      ->condition('type', 'article')
      ->count()
      ->execute();
    $this->assertGreaterThan(0, $count);

    // Uninstall the module.
    $module_installer->uninstall(['demo_umami_content']);

    // Reset storage cache.
    $block_storage->resetCache();
    $node_storage->resetCache();

    // Assert the removal of blocks on uninstall.
    $count = $block_storage->getQuery()
      ->condition('type', 'banner_block')
      ->count()
      ->execute();
    $this->assertEquals(0, $count);
    $this->assertNull($this->container->get('entity_type.manager')->getStorage('block')->load('umami_banner_recipes'));

    // Assert the removal of nodes on uninstall.
    $count = $node_storage->getQuery()
      ->condition('type', 'article')
      ->count()
      ->execute();
    $this->assertEquals(0, $count);

    $count = $node_storage->getQuery()
      ->condition('type', 'recipe')
      ->count()
      ->execute();
    $this->assertEquals(0, $count);

    // Re-install and assert imported content.
    $module_installer->install(['demo_umami_content']);
    $this->assertRecipesImported($node_storage);
    $this->assertImportedCustomBlock($block_storage);

  }

  /**
   * Assert recipes are imported.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   Node storage.
   */
  protected function assertRecipesImported(EntityStorageInterface $node_storage) {
    $count = $node_storage->getQuery()
      ->condition('type', 'recipe')
      ->count()
      ->execute();
    $this->assertGreaterThan(0, $count);
    $nodes = $node_storage->loadByProperties(['title' => 'Gluten free pizza']);
    $this->assertCount(1, $nodes);
  }

  /**
   * Assert block content are imported.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_storage
   *   Block storage.
   */
  protected function assertImportedCustomBlock(EntityStorageInterface $block_storage) {
    // Verify that block is placed.
    $assert = $this->assertSession();
    $this->drupalGet('/recipes');
    $assert->pageTextContains('Super easy vegetarian pasta bake');

    $count = $block_storage->getQuery()
      ->condition('type', 'banner_block')
      ->count()
      ->execute();
    $this->assertGreaterThan(0, $count);
    $block = $block_storage->loadByProperties(['uuid' => '4c7d58a3-a45d-412d-9068-259c57e40541']);
    $this->assertCount(1, $block);
  }

}
