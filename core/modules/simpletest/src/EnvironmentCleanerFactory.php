<?php

namespace Drupal\simpletest;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Test\TestDatabase;
use Drupal\Core\Database\Database;

/**
 * Test environment cleaner factory.
 *
 * We use a factory pattern here so that we can inject the test results database
 * which is not a service (and should not be).
 */
class EnvironmentCleanerFactory {

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $container;

  /**
   * Construct an environment cleaner factory.
   *
   * @param \Drupal\Core\DependencyInjection\Container $container
   *   The container.
   */
  public function __construct(Container $container) {
    $this->container = $container;
  }

  /**
   * Factory method to create the environment cleaner service.
   *
   * @return \Drupal\simpletest\EnvironmentCleanerService
   *   The environment cleaner service.
   */
  public function createCleaner() {
    $cleaner = new EnvironmentCleanerService(
      $this->container->get('app.root'),
      Database::getConnection(),
      TestDatabase::getConnection(),
      $this->container->get('messenger'),
      $this->container->get('string_translation'),
      $this->container->get('config.factory'),
      $this->container->get('cache.default'),
      $this->container->get('file_system')
    );

    return $cleaner;
  }

}
