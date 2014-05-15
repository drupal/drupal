<?php

/**
 * @file
 * Definition of Drupal\Core\DrupalKernelInterface.
 */

namespace Drupal\Core;

use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * The interface for DrupalKernel, the core of Drupal.
 *
 * This interface extends Symfony's KernelInterface and adds methods for
 * responding to modules being enabled or disabled during its lifetime.
 */
interface DrupalKernelInterface extends HttpKernelInterface {

  /**
   * Boots the current kernel.
   */
  public function boot();

  /**
   * Shuts down the kernel.
   */
  public function shutdown();

  /**
   * Discovers available serviceProviders.
   *
   * @return array
   *   The available serviceProviders.
   */
  public function discoverServiceProviders();

  /**
   * Returns all registered service providers.
   *
   * @return array
   *   An associative array of ServiceProvider objects, keyed by name.
   */
  public function getServiceProviders();

  /**
   * Gets the current container.
   *
   * @return ContainerInterface A ContainerInterface instance
   */
  public function getContainer();

  /**
   * Updates the kernel's list of modules to the new list.
   *
   * The kernel needs to update its bundle list and container to match the new
   * list.
   *
   * @param array $module_list
   *   The new list of modules.
   * @param array $module_filenames
   *   List of module filenames, keyed by module name.
   */
  public function updateModules(array $module_list, array $module_filenames = array());
}
