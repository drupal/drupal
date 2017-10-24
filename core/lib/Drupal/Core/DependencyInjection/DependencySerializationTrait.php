<?php

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dependency injection friendly methods for serialization.
 */
trait DependencySerializationTrait {

  /**
   * An array of service IDs keyed by property name used for serialization.
   *
   * @var array
   */
  protected $_serviceIds = [];

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->_serviceIds = [];
    $vars = get_object_vars($this);
    foreach ($vars as $key => $value) {
      if (is_object($value) && isset($value->_serviceId)) {
        // If a class member was instantiated by the dependency injection
        // container, only store its ID so it can be used to get a fresh object
        // on unserialization.
        $this->_serviceIds[$key] = $value->_serviceId;
        unset($vars[$key]);
      }
      // Special case the container, which might not have a service ID.
      elseif ($value instanceof ContainerInterface) {
        $this->_serviceIds[$key] = 'service_container';
        unset($vars[$key]);
      }
    }

    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    // Tests in isolation potentially unserialize in the parent process.
    $phpunit_bootstrap = isset($GLOBALS['__PHPUNIT_BOOTSTRAP']);
    if ($phpunit_bootstrap && !\Drupal::hasContainer()) {
      return;
    }
    $container = \Drupal::getContainer();
    foreach ($this->_serviceIds as $key => $service_id) {
      // In rare cases, when test data is serialized in the parent process,
      // there is a service container but it doesn't contain all expected
      // services. To avoid fatal errors during the wrap-up of failing tests, we
      // check for this case, too.
      if ($phpunit_bootstrap && !$container->has($service_id)) {
        continue;
      }
      $this->$key = $container->get($service_id);
    }
    $this->_serviceIds = [];
  }

}
