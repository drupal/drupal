<?php

/**
 * @file
 * Contains \Drupal\filter\Entity\FilterFormatInterface.
 */

namespace Drupal\filter;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a filter format entity.
 */
interface FilterFormatInterface extends ConfigEntityInterface {

  /**
   * Returns the collection of filter pugin instances or an individual plugin instance.
   *
   * @param string $instance_id
   *   (optional) The ID of a filter plugin instance to return.
   *
   * @return \Drupal\filter\FilterBag|\Drupal\filter\Plugin\FilterInterface
   *   Either the filter bag or a specific filter plugin instance.
   */
  public function filters($instance_id = NULL);

  /**
   * Sets the configuration for a filter plugin instance.
   *
   * Sets or replaces the configuration of a filter plugin in $this->filters,
   * and if instantiated already, also ensures that the actual filter plugin on
   * the FilterBag contains the identical configuration.
   *
   * @param string $instance_id
   *   The ID of a filter plugin to set the configuration for.
   * @param array $configuration
   *   The filter plugin configuration to set.
   */
  public function setFilterConfig($instance_id, array $configuration);

  /**
   * Returns if this format is the fallback format.
   *
   * The fallback format can never be disabled. It must always be available.
   *
   * @return bool
   *   TRUE if this format is the fallback format, FALSE otherwise.
   */
  public function isFallbackFormat();

  /**
   * Returns the machine-readable permission name for the text format.
   *
   * @return string|bool
   *   The machine-readable permission name, or FALSE if the text format is
   *   malformed or is the fallback format (which is available to all users).
   */
  public function getPermissionName();

}
