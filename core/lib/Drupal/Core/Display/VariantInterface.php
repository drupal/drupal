<?php

/**
 * @file
 * Contains \Drupal\Core\Display\VariantInterface.
 */

namespace Drupal\Core\Display;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for DisplayVariant plugins.
 *
 * @see \Drupal\Core\Display\Annotation\DisplayVariant
 * @see \Drupal\Core\Display\VariantBase
 * @see \Drupal\Core\Display\VariantManager
 * @see plugin_api
 */
interface VariantInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface, RefinableCacheableDependencyInterface {

  /**
   * Returns the user-facing display variant label.
   *
   * @return string
   *   The display variant label.
   */
  public function label();

  /**
   * Returns the admin-facing display variant label.
   *
   * This is for the type of display variant, not the configured variant itself.
   *
   * @return string
   *   The display variant administrative label.
   */
  public function adminLabel();

  /**
   * Returns the unique ID for the display variant.
   *
   * @return string
   *   The display variant ID.
   */
  public function id();

  /**
   * Returns the weight of the display variant.
   *
   * @return int
   *   The display variant weight.
   */
  public function getWeight();

  /**
   * Sets the weight of the display variant.
   *
   * @param int $weight
   *   The weight to set.
   */
  public function setWeight($weight);

  /**
   * Determines if this display variant is accessible.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if this display variant is accessible, FALSE otherwise.
   */
  public function access(AccountInterface $account = NULL);

  /**
   * Builds and returns the renderable array for the display variant.
   *
   * The variant can contain cacheability metadata for the configuration that
   * was passed in setConfiguration(). In the build() method, this should be
   * added to the render array that is returned.
   *
   * @return array
   *   A render array for the display variant.
   */
  public function build();

}
