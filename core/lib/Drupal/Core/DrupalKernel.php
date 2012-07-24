<?php

/**
 * @file
 * Definition of Drupal\Core\DrupalKernel.
 */

namespace Drupal\Core;

use Drupal\Core\CoreBundle;
use Symfony\Component\HttpKernel\Kernel;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * The DrupalKernel class is the core of Drupal itself.
 */
class DrupalKernel extends Kernel {

  public function registerBundles() {
    $bundles = array(
      new CoreBundle(),
    );

    // TODO: Somehow remove the necessity of calling system_list() to find out which
    // bundles exist.

    $modules = array_keys(system_list('module_enabled'));
    foreach ($modules as $module) {
      $camelized = ContainerBuilder::camelize($module);
      $class = "\Drupal\\{$module}\\{$camelized}Bundle";
      if (class_exists($class)) {
        $bundles[] = new $class();
      }
    }
    return $bundles;
  }


  /**
   * Initializes the service container.
   */
  protected function initializeContainer() {
    // @todo We should be compiling the container and dumping to php so we don't
    // have to recompile every time. There is a separate issue for this, see
    // http://drupal.org/node/1668892.
    $this->container = $this->buildContainer();
    $this->container->set('kernel', $this);
    drupal_container($this->container);
  }

  /**
   * Builds the service container.
   *
   * @return ContainerBuilder The compiled service container
   */
  protected function buildContainer() {
    $container = $this->getContainerBuilder();

    if ($bootstrap_container = drupal_container()) {
      $container->merge($bootstrap_container);
    }
    foreach ($this->bundles as $bundle) {
      $bundle->build($container);
    }
    $container->compile();
    return $container;
  }

  /**
   * Gets a new ContainerBuilder instance used to build the service container.
   *
   * @return ContainerBuilder
   */
  protected function getContainerBuilder() {
    return new ContainerBuilder(new ParameterBag($this->getKernelParameters()));
  }

  /**
   * This method is part of the KernelInterface interface, but takes an object
   * implementing LoaderInterface as its only parameter. This is part of the
   * Config compoment from Symfony, which is not provided by Drupal core.
   *
   * Modules wishing to provide an extension to this class which uses this
   * method are responsible for ensuring the Config component exists.
   */
  public function registerContainerConfiguration(LoaderInterface $loader) {
  }
}
