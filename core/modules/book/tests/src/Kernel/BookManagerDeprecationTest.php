<?php

namespace Drupal\Tests\book\Kernel;

use Drupal\book\BookManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\book\BookManager
 * @group legacy
 */
class BookManagerDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['book'];

  /**
   * @param string $method
   *   Method to be tested.
   *
   * @dataProvider providerTestDrupalStaticResetDeprecation
   * @see drupal_static_reset()
   */
  public function testDrupalStaticResetDeprecation(string $method): void {
    $this->expectDeprecation("Calling drupal_static_reset() with '{$method}' as argument is deprecated in drupal:9.3.0 and is removed in drupal:10.0.0. Use \Drupal::service('book.memory_cache')->deleteAll() instead. See https://www.drupal.org/node/3039439");
    drupal_static_reset($method);
  }

  /**
   * Provides test cases for ::testDrupalStaticResetDeprecation().
   *
   * @return string[][]
   *   Test cases for ::testDrupalStaticResetDeprecation().
   */
  public function providerTestDrupalStaticResetDeprecation(): array {
    return [
      ['Drupal\book\BookManager::bookSubtreeData'],
      ['Drupal\book\BookManager::bookTreeAllData'],
      ['Drupal\book\BookManager::doBookTreeBuild'],
    ];
  }

  /**
   * @covers ::__construct
   */
  public function testOptionalParametersDeprecation(): void {
    $this->expectDeprecation('Calling BookManager::__construct() without the $backend_chained_cache argument is deprecated in drupal:9.3.0 and the $backend_chained_cache argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3039439');
    $this->expectDeprecation('Calling BookManager::__construct() without the $memory_cache argument is deprecated in drupal:9.3.0 and the $memory_cache argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3039439');
    new BookManager(
      $this->container->get('entity_type.manager'),
      $this->container->get('string_translation'),
      $this->container->get('config.factory'),
      $this->container->get('book.outline_storage'),
      $this->container->get('renderer'),
      $this->container->get('language_manager'),
      $this->container->get('entity.repository')
    );
  }

}
