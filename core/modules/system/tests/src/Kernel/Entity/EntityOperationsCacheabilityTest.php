<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests cacheability added by entity operations.
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class EntityOperationsCacheabilityTest extends EntityKernelTestBase {

  /**
   * Test cacheability is added via entity operations functions and hooks.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::getOperations()
   * @see \Drupal\Core\Entity\EntityListBuilder::getDefaultOperations()
   * @see \Drupal\entity_test\Hook\EntityTestHooks::entityOperation()
   * @see \Drupal\entity_test\Hook\EntityTestHooks::entityOperationAlter()
   */
  #[DataProvider('providerEntityOperationsCacheability')]
  public function testEntityOperationsCacheability(array $modules, string $entityType, array $properties, array $expectedTags, array $expectedContexts, bool $createBundle = FALSE, bool $setId = FALSE): void {
    if ($entityType === 'block') {
      \Drupal::service('theme_installer')->install(['stark']);
    }
    elseif (str_contains($entityType, 'image')) {
      \Drupal::configFactory()->getEditable('system.image')->set('toolkit', 'gd')->save();
    }

    \Drupal::service('module_installer')->install($modules);
    $entityTypeManager = \Drupal::entityTypeManager();
    $storage = $entityTypeManager->getStorage($entityType);
    $entityTypeDef = $entityTypeManager->getDefinition($entityType);
    if ($createBundle) {
      $bundleType = $entityTypeDef->getBundleEntityType();
      $bundleTypeDef = $entityTypeManager->getDefinition($bundleType);
      $entityTypeManager->getStorage($bundleType)->create([
        $bundleTypeDef->getKey('id') => 'test',
        $bundleTypeDef->getKey('label') => 'test',
      ])->save();
    }
    if ($entityTypeDef->getKey('label')) {
      $properties += [$entityTypeDef->getKey('label') => 'Test entity'];
    }
    if ($setId) {
      $properties += [$entityTypeDef->getKey('id') => 'test'];
    }
    if ($createBundle) {
      $properties += [$entityTypeDef->getKey('bundle') => 'test'];
    }
    $entity = $storage->create($properties);
    $entity->save();

    $listBuilder = $entityTypeManager->getListBuilder($entityType);
    $cacheability = new CacheableMetadata();
    $listBuilder->getOperations($entity, $cacheability);
    $this->assertSame($expectedTags, $cacheability->getCacheTags());
    $this->assertEquals($expectedContexts, $cacheability->getCacheContexts());
    $this->assertEquals(-1, $cacheability->getCacheMaxAge());
  }

  /**
   * Data provider for testEntityOperationsCacheability().
   */
  public static function providerEntityOperationsCacheability(): iterable {
    yield [
      ['entity_test'],
      'entity_test',
      [],
      ['entity_test_operation_tag_test', 'entity_test_operation_alter_tag_test'],
      ['user.permissions'],
    ];
    yield [
      ['block'],
      'block',
      ['plugin' => 'broken', 'theme' => 'stark', 'settings' => ['label' => 'test']],
      ['config:block_list'],
      ['user.permissions'],
      FALSE,
      TRUE,
    ];
    yield [['block_content'], 'block_content', [], ['block_content:1'], ['user.permissions'], TRUE];
    yield [['comment'], 'comment_type', [], [], ['user.permissions'], FALSE, TRUE];
    yield [
      ['field_ui'],
      'entity_view_mode',
      ['targetEntityType' => 'entity_test', 'id' => 'entity_test.test'],
      [],
      ['user.permissions'],
      FALSE,
      TRUE,
    ];
    yield [['filter'], 'filter_format', [], [], ['user.permissions'], FALSE, TRUE];
    yield [['image'], 'image_style', [], [], ['user.permissions'], FALSE, TRUE];
    yield [['menu_link_content'], 'menu_link_content', ['link' => 'route:<nolink>'], [], ['user.permissions']];
    yield [['menu_ui'], 'menu', [], ['config:system.menu.test'], ['user.permissions'], FALSE, TRUE];
    yield [['node'], 'node_type', [], ['config:node.type.test'], ['user.permissions'], FALSE, TRUE];
    yield [['responsive_image'], 'responsive_image_style', [], [], ['user.permissions'], FALSE, TRUE];
    yield [
      ['user', 'search'],
      'search_page',
      ['plugin' => 'user_search', 'path' => '/test_user_search'],
      ['config:search.page.test'],
      ['user.permissions'],
      FALSE,
      TRUE,
    ];
    yield [['shortcut'], 'shortcut_set', [], [], ['user.permissions'], FALSE, TRUE];
    yield [['taxonomy'], 'taxonomy_vocabulary', [], [], ['user.permissions'], FALSE, TRUE];
    yield [['user'], 'user_role', [], [], ['user.permissions'], FALSE, TRUE];
    yield [['user'], 'user', ['name' => 'test'], [], ['user', 'user.permissions']];
    yield [['views_ui'] , 'view', [], [], ['user.permissions'], FALSE, TRUE];
    yield [['workspaces_ui'], 'workspace', [], [], ['user.permissions', 'workspace'], FALSE, TRUE];
  }

}
