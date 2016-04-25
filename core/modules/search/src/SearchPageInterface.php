<?php

namespace Drupal\search;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a search page entity.
 */
interface SearchPageInterface extends ConfigEntityInterface {

  /**
   * Returns the search plugin.
   *
   * @return \Drupal\search\Plugin\SearchInterface
   *   The search plugin used by this search page entity.
   */
  public function getPlugin();

  /**
   * Sets the search plugin.
   *
   * @param string $plugin_id
   *   The search plugin ID.
   */
  public function setPlugin($plugin_id);

  /**
   * Determines if this search page entity is currently the default search.
   *
   * @return bool
   *   TRUE if this search page entity is the default search, FALSE otherwise.
   */
  public function isDefaultSearch();

  /**
   * Determines if this search page entity is indexable.
   *
   * @return bool
   *   TRUE if this search page entity is indexable, FALSE otherwise.
   */
  public function isIndexable();

  /**
   * Returns the path for the search.
   *
   * @return string
   *  The part of the path for this search page that comes after 'search'.
   */
  public function getPath();

  /**
   * Returns the weight for the page.
   *
   * @return int
   *   The page weight.
   */
  public function getWeight();

}
