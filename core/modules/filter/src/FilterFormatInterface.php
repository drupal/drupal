<?php

namespace Drupal\filter;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a filter format entity.
 */
interface FilterFormatInterface extends ConfigEntityInterface {

  /**
   * Returns the ordered collection of filter plugin instances or an individual plugin instance.
   *
   * @param string $instance_id
   *   (optional) The ID of a filter plugin instance to return.
   *
   * @return \Drupal\filter\FilterPluginCollection|\Drupal\filter\Plugin\FilterInterface
   *   Either the filter collection or a specific filter plugin instance.
   */
  public function filters($instance_id = NULL);

  /**
   * Sets the configuration for a filter plugin instance.
   *
   * Sets or replaces the configuration of a filter plugin in $this->filters,
   * and if instantiated already, also ensures that the actual filter plugin on
   * the FilterPluginCollection contains the identical configuration.
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

  /**
   * Retrieves all filter types that are used in the text format.
   *
   * @return array
   *   All filter types used by filters of the text format.
   */
  public function getFilterTypes();

  /**
   * Retrieve all HTML restrictions (tags and attributes) for the text format.
   *
   * Note that restrictions applied to the "*" tag (the wildcard tag, i.e. all
   * tags) are treated just like any other HTML tag. That means that any
   * restrictions applied to it are not automatically applied to all other tags.
   * It is up to the caller to handle this in whatever way it sees fit; this way
   * no information granularity is lost.
   *
   * @return array|false
   *   A structured array as returned by FilterInterface::getHTMLRestrictions(),
   *   but with the intersection of all filters in this text format. The
   *   restrictions will either forbid or allow a list of tags. In the latter
   *   case, it's possible that restrictions on attributes are also stored.
   *   FALSE means there are no HTML restrictions.
   */
  public function getHtmlRestrictions();

  /**
   * Removes a filter.
   *
   * @param string $instance_id
   *   The ID of a filter plugin to be removed.
   */
  public function removeFilter($instance_id);

}
