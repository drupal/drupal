<?php

namespace Drupal\path_alias;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface defining a path_alias entity.
 */
interface PathAliasInterface extends ContentEntityInterface, EntityPublishedInterface {

  /**
   * Gets the source path of the alias.
   *
   * @return string
   *   The source path.
   */
  public function getPath();

  /**
   * Sets the source path of the alias.
   *
   * @param string $path
   *   The source path.
   *
   * @return $this
   */
  public function setPath($path);

  /**
   * Gets the alias for this path.
   *
   * @return string
   *   The alias for this path.
   */
  public function getAlias();

  /**
   * Sets the alias for this path.
   *
   * @param string $alias
   *   The path alias.
   *
   * @return $this
   */
  public function setAlias($alias);

}
