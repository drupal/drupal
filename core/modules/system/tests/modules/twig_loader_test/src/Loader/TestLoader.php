<?php

/**
 * @file
 * Contains \Drupal\twig_loader_test\Loader\TestLoader.
 */

namespace Drupal\twig_loader_test\Loader;

use Drupal\Core\Template\Loader;

/**
 * A test Twig loader.
 */
class TestLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface {

  /**
   * {@inheritdoc}
   */
  public function getSource($name) {
    if ($name == 'kittens') {
      return $name;
    }
    else {
      return 'cats';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKey($name) {
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function isFresh($name, $time) {
    return TRUE;
  }

}
