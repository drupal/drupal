<?php

namespace Drupal\twig_loader_test\Loader;

use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * A test Twig loader.
 */
class TestLoader implements LoaderInterface {

  /**
   * {@inheritdoc}
   */
  public function getSourceContext(string $name): Source {
    $name = (string) $name;
    $value = $name === 'kittens' ? 'kittens' : 'cats';
    return new Source($value, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function exists(string $name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKey(string $name): string {
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function isFresh(string $name, int $time): bool {
    return TRUE;
  }

}
