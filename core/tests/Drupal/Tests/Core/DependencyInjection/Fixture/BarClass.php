<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\Fixture\BarClass.
 */

namespace Drupal\Tests\Core\DependencyInjection\Fixture;

/**
 * Stub class which acts as a service to test the container.
 *
 * @see \Drupal\Tests\Core\DependencyInjection\ContainerBuilderTest
 * @see \Drupal\Tests\Core\DependencyInjection\Fixture\BazClass
 */
class BarClass {

  /**
   * Storage for a protected BazClass object.
   *
   * @var Drupal\Tests\Core\DependencyInjection\Fixture\BazClass
   */
  protected $baz;

  /**
   * Setter for our BazClass object.
   *
   * This method is called during the service initialization.
   *
   * @param \Drupal\Tests\Core\DependencyInjection\Fixture\BazClass $baz
   *   A BazClass object to store.
   */
  public function setBaz(BazClass $baz) {
    $this->baz = $baz;
  }

  /**
   * Getter for our BazClass object.
   *
   * @return \Drupal\Tests\Core\DependencyInjection\Fixture\BazClass
   *   The stored BazClass object.
   */
  public function getBaz() {
    return $this->baz;
  }

}
