<?php

namespace Drupal\Core\Test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Component\DependencyInjection\ReverseContainer;
use Drupal\Core\DrupalKernel;

/**
 * Kernel to mock requests to test simpletest.
 */
class TestKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  public function __construct($environment, $class_loader, $allow_dumping = TRUE) {
    // Exit if we should be in a test environment but aren't.
    if (!drupal_valid_test_ua()) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
      exit;
    }

    parent::__construct($environment, $class_loader, $allow_dumping);
  }

  /**
   * Sets a container with a kernel service on the Drupal class.
   *
   * @return \Drupal\Component\DependencyInjection\ContainerInterface
   *   A container with the kernel service set.
   */
  public static function setContainerWithKernel() {
    $container = new ContainerBuilder();
    $kernel = new DrupalKernel('test', NULL);
    // Objects of the same type will have access to each others private and
    // protected members even though they are not the same instances. This is
    // because the implementation specific details are already known when
    // inside those objects.
    $kernel->container = $container;
    $container->set('kernel', $kernel);
    $container->set(ReverseContainer::class, new ReverseContainer($container));
    \Drupal::setContainer($container);
    return $container;
  }

}
