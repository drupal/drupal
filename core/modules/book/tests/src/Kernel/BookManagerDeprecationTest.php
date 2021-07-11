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
   * @see drupal_static_reset()
   */
  public function testDrupalStaticResetDeprecation(): void {
    foreach (['bookSubtreeData', 'bookTreeAllData', 'doBookTreeBuild'] as $method) {
      $this->expectDeprecation("Calling drupal_static_reset() with 'Drupal\book\BookManager::{$method}' as argument is deprecated in drupal:9.3.0 and is removed in drupal:10.0.0. Use \Drupal::service('book.memory_cache')->deleteAll() instead. See https://www.drupal.org/node/3039439");
      drupal_static_reset("Drupal\book\BookManager::{$method}");
    }
  }

  /**
   * @covers ::__construct
   */
  public function testOptionalParametersDeprecation(): void {
    $this->expectDeprecation('Calling BookManager::__construct() without the $book_cache argument is deprecated in drupal:9.3.0 and the $book_cache argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3039439');
    $this->expectDeprecation('Calling BookManager::__construct() without the $book_memory_cache argument is deprecated in drupal:9.3.0 and the $book_memory_cache argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3039439');
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
