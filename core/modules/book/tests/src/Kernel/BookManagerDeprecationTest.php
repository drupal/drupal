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
   * @expectedDeprecation Using drupal_static_reset() with 'Drupal\book\BookManager::bookSubtreeData' as argument is deprecated in drupal:8.8.0 and will be removed before drupal:9.0.0. Use \Drupal::service('book.cache')->deleteAll() instead. See https://www.drupal.org/node/3039439.
   * @see drupal_static_reset()
   */
  public function testBookSubtreeDataDrupalStaticCacheDeprecation() {
    drupal_static_reset('Drupal\book\BookManager::bookSubtreeData');
    // Ensure at least one assertion.
    $this->assertTrue(TRUE);
  }

  /**
   * @expectedDeprecation Using drupal_static_reset() with 'Drupal\book\BookManager::bookTreeAllData' as argument is deprecated in drupal:8.8.0 and will be removed before drupal:9.0.0. Use \Drupal::service('book.cache')->deleteAll() instead. See https://www.drupal.org/node/3039439.
   * @see drupal_static_reset()
   */
  public function testBookTreeAllDataDrupalStaticCacheDeprecation() {
    drupal_static_reset('Drupal\book\BookManager::bookTreeAllData');
    // Ensure at least one assertion.
    $this->assertTrue(TRUE);
  }

  /**
   * @expectedDeprecation Using drupal_static_reset() with 'Drupal\book\BookManager::doBookTreeBuild' as argument is deprecated in drupal:8.8.0 and will be removed before drupal:9.0.0. Use \Drupal::service('book.cache')->deleteAll() instead. See https://www.drupal.org/node/3039439.
   * @see drupal_static_reset()
   */
  public function testDoBookTreeBuildDrupalStaticCacheDeprecation() {
    drupal_static_reset('Drupal\book\BookManager::doBookTreeBuild');
    // Ensure at least one assertion.
    $this->assertTrue(TRUE);
  }

  /**
   * @covers ::__construct
   * @expectedDeprecation Calling BookManager::__construct() without the $book_cache argument is deprecated in drupal:8.8.0. The $book_cache argument will be required in drupal:9.0.0. See https://www.drupal.org/node/3039439
   */
  public function testBackendChainOptionalParameterDeprecation() {
    new BookManager(
      $this->container->get('entity_type.manager'),
      $this->container->get('string_translation'),
      $this->container->get('config.factory'),
      $this->container->get('book.outline_storage'),
      $this->container->get('renderer'),
      // The optional parameter is passed as NULL.
      NULL,
      $this->container->get('book.memory_cache')
    );
    // Ensure at least one assertion.
    $this->assertTrue(TRUE);
  }

  /**
   * @covers ::__construct
   * @expectedDeprecation Calling BookManager::__construct() without the $book_memory_cache argument is deprecated in drupal:8.8.0. The $book_memory_cache argument will be required in drupal:9.0.0. See https://www.drupal.org/node/3039439
   */
  public function testMemoryCacheOptionalParameterDeprecation() {
    new BookManager(
      $this->container->get('entity_type.manager'),
      $this->container->get('string_translation'),
      $this->container->get('config.factory'),
      $this->container->get('book.outline_storage'),
      $this->container->get('renderer'),
      $this->container->get('book.cache')
      // The optional parameter is not passed.
    );
    // Ensure at least one assertion.
    $this->assertTrue(TRUE);
  }

}
