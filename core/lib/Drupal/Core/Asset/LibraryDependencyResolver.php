<?php

namespace Drupal\Core\Asset;

/**
 * Resolves the dependencies of asset (CSS/JavaScript) libraries.
 */
class LibraryDependencyResolver implements LibraryDependencyResolverInterface {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The libraries dependencies.
   *
   * @var array
   */
  protected $librariesDependencies = [];

  /**
   * Constructs a new LibraryDependencyResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery) {
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesWithDependencies(array $libraries) {
    $return = [];
    foreach ($libraries as $library) {
      if (!isset($this->librariesDependencies[$library])) {
        $this->librariesDependencies[$library] = $this->doGetDependencies([$library]);
      }
      $return += $this->librariesDependencies[$library];
    }
    return array_values($return);
  }

  /**
   * Gets the given libraries with its dependencies.
   *
   * Helper method for ::getLibrariesWithDependencies().
   *
   * @param string[] $libraries_with_unresolved_dependencies
   *   A list of libraries, with unresolved dependencies, in the order they
   *   should be loaded.
   * @param string[] $final_libraries
   *   The final list of libraries (the return value) that is being built
   *   recursively.
   *
   * @return string[]
   *   A list of libraries, in the order they should be loaded, including their
   *   dependencies.
   */
  protected function doGetDependencies(array $libraries_with_unresolved_dependencies, array $final_libraries = []) {
    foreach ($libraries_with_unresolved_dependencies as $library) {
      if (!isset($final_libraries[$library])) {
        [$extension, $name] = explode('/', $library, 2);
        $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
        if (!empty($definition['dependencies'])) {
          $final_libraries = $this->doGetDependencies($definition['dependencies'], $final_libraries);
        }
        $final_libraries[$library] = $library;
      }
    }
    return $final_libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinimalRepresentativeSubset(array $libraries) {
    assert(count($libraries) === count(array_unique($libraries)), '$libraries can\'t contain duplicate items.');

    // Determine each library's dependencies.
    $all_dependencies = [];
    foreach ($libraries as $library) {
      $with_deps = $this->getLibrariesWithDependencies([$library]);
      // We don't need library itself listed in the dependencies.
      $all_dependencies = array_unique(array_merge($all_dependencies, array_diff($with_deps, [$library])));
    }

    return array_values(array_diff($libraries, array_intersect($all_dependencies, $libraries)));
  }

}
