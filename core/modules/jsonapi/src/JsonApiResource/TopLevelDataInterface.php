<?php

namespace Drupal\jsonapi\JsonApiResource;

/**
 * Interface for objects that can appear as top-level object data.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
interface TopLevelDataInterface {

  /**
   * Returns the data for the top-level data member of a JSON:API document.
   *
   * @return \Drupal\jsonapi\JsonApiResource\Data
   *   The top-level data.
   */
  public function getData();

  /**
   * Returns the data that was omitted from the JSON:API document.
   *
   * @return \Drupal\jsonapi\JsonApiResource\OmittedData
   *   The omitted data.
   */
  public function getOmissions();

  /**
   * Merges the object's links with the top-level links.
   *
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $top_level_links
   *   The top-level links to merge.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The merged links.
   */
  public function getMergedLinks(LinkCollection $top_level_links);

  /**
   * Merges the object's meta member with the top-level meta member.
   *
   * @param array $top_level_meta
   *   The top-level links to merge.
   *
   * @return array
   *   The merged meta member.
   */
  public function getMergedMeta(array $top_level_meta);

}
