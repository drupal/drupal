<?php

namespace Drupal\twig_loader_test\Loader;

use Twig\Source;

/**
 * A test Twig loader.
 */
class TestLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface, \Twig_SourceContextLoaderInterface {

  /**
   * {@inheritdoc}
   */
  public function getSourceContext($name) {
    $name = (string) $name;
    $value = $name === 'kittens' ? 'kittens' : 'cats';
    return new Source($value, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getSource($name) {
    return $this->getSourceContext($name)->getCode();
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
