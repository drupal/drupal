<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDependencyResolverInterface.
 */

namespace Drupal\Core\Asset;

/**
 * Resolves the dependencies of asset (CSS/JavaScript) libraries.
 */
interface LibraryDependencyResolverInterface {

  /**
   * Gets the given libraries with their dependencies.
   *
   * Given ['core/a', 'core/b', 'core/c'], with core/a depending on core/c and
   * core/b on core/d, returns ['core/a', 'core/b', 'core/c', 'core/d'].
   *
   * @param string[] $libraries
   *   A list of libraries, in the order they should be loaded.
   *
   * @return string[]
   *   A list of libraries, in the order they should be loaded, including their
   *   dependencies.
   */
  public function getLibrariesWithDependencies(array $libraries);

  /**
   * Gets the minimal representative subset of the given libraries.
   *
   * A minimal representative subset means that any library in the given set of
   * libraries that is a dependency of another library in the set, is removed.
   *
   * Hence a minimal representative subset is the most compact representation
   * possible of a set of libraries.
   *
   * (Each asset library has dependencies and can therefore be seen as a tree.
   * Hence the given list of libraries represent a forest. This function returns
   * all roots of trees that are not a subtree of another tree in the forest.)
   *
   * @param string[] $libraries
   *   A set of libraries.
   *
   * @return string[]
   *   A representative subset of the given set of libraries.
   */
  public function getMinimalRepresentativeSubset(array $libraries);

}
