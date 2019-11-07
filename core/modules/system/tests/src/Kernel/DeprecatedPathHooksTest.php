<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Path\AliasStorage
 *
 * @group path
 * @group legacy
 */
class DeprecatedPathHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'path_alias', 'path_deprecated_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('path_alias');
  }

  /**
   * @covers ::save
   *
   * @expectedDeprecation The deprecated hook hook_path_insert() is implemented in these functions: path_deprecated_test_path_insert(). It will be removed before Drupal 9.0.0. Use hook_ENTITY_TYPE_insert() for the 'path_alias' entity type instead. See https://www.drupal.org/node/3013865.
   */
  public function testInsert() {
    $source = '/' . $this->randomMachineName();
    $alias = '/' . $this->randomMachineName();

    $alias_storage = \Drupal::service('path.alias_storage');
    $alias_storage->save($source, $alias);
  }

  /**
   * @covers ::save
   *
   * @expectedDeprecation The deprecated hook hook_path_update() is implemented in these functions: path_deprecated_test_path_update(). It will be removed before Drupal 9.0.0. Use hook_ENTITY_TYPE_update() for the 'path_alias' entity type instead. See https://www.drupal.org/node/3013865.
   */
  public function testUpdate() {
    $source = '/' . $this->randomMachineName();
    $alias = '/' . $this->randomMachineName();

    $alias_storage = \Drupal::service('path.alias_storage');
    $alias_storage->save($source, $alias);

    $new_source = '/' . $this->randomMachineName();
    $path = $alias_storage->load(['source' => $source]);
    $alias_storage->save($new_source, $alias, LanguageInterface::LANGCODE_NOT_SPECIFIED, $path['pid']);
  }

  /**
   * @covers ::delete
   *
   * @expectedDeprecation The deprecated hook hook_path_delete() is implemented in these functions: path_deprecated_test_path_delete(). It will be removed before Drupal 9.0.0. Use hook_ENTITY_TYPE_delete() for the 'path_alias' entity type instead. See https://www.drupal.org/node/3013865.
   */
  public function testDelete() {
    $source = '/' . $this->randomMachineName();
    $alias = '/' . $this->randomMachineName();

    $alias_storage = \Drupal::service('path.alias_storage');
    $alias_storage->save($source, $alias);

    $path = $alias_storage->load(['source' => $source]);
    $alias_storage->delete(['pid' => $path['pid']]);
  }

}
