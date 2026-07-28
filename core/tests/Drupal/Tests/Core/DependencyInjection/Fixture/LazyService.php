<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection\Fixture;

/**
 * A service used to test the handling of lazy service definitions.
 *
 * @see \Drupal\Tests\ProxyClass\Core\DependencyInjection\Fixture\LazyService
 * @see \Drupal\Tests\Core\DependencyInjection\Compiler\ProxyServicesPassTest
 */
class LazyService {

  /**
   * Returns a value.
   *
   * @return string
   *   A value.
   */
  public function getValue(): string {
    return 'value';
  }

}
