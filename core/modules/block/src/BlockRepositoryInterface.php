<?php

namespace Drupal\block;

/**
 * Provides an interface for the block repository.
 */
interface BlockRepositoryInterface {

  /**
   * Return only visible regions.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Instead,
   *   use \Drupal\Core\Extension\Theme::listVisibleRegions().
   *
   * @see https://www.drupal.org/node/3015925
   */
  const REGIONS_VISIBLE = 'visible';

  /**
   * Return all regions.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Instead,
   *   use \Drupal\Core\Extension\Theme::listAllRegions().
   *
   * @see https://www.drupal.org/node/3015925
   */
  const REGIONS_ALL = 'all';

  /**
   * Returns an array of regions and their block entities.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata[] $cacheable_metadata
   *   (optional) List of CacheableMetadata objects, keyed by region. This is
   *   by reference and is used to pass this information back to the caller.
   *
   * @return array
   *   The array is first keyed by region machine name, with the values
   *   containing an array keyed by block ID, with block entities as the values.
   */
  public function getVisibleBlocksPerRegion(array &$cacheable_metadata = []);

  /**
   * Based on a suggested string generates a unique machine name for a block.
   *
   * @param string $suggestion
   *   The suggested block ID.
   * @param string $theme
   *   The machine name of the theme.
   *
   * @return string
   *   Returns the unique name.
   */
  public function getUniqueMachineName(string $suggestion, string $theme): string;

}
