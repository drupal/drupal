<?php

namespace Drupal\Core\Asset;

use Drupal\Component\Graph\Graph;

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
    $libraries_graph = $this->doGetDependencies($libraries);
    $libraries_graph = $this->doProcessBeforeAfter($libraries_graph);

    $graph_object = new Graph($libraries_graph);
    $graph = $graph_object->searchAndSort();

    uasort($graph, function ($a, $b) {
      if ($a['weight'] == $b['weight']) {
        return 0;
      }
      return ($a['weight'] < $b['weight']) ? 1 : -1;
    });

    return array_keys($graph);
  }

  /**
   * Gets the given libraries with its dependencies.
   *
   * Helper method for ::getLibrariesWithDependencies().
   *
   * @param string[] $libraries
   *   A list of libraries in the order they should be loaded.
   * @param array $graph
   *   The graph of libraries that is being built recursively.
   *
   * @return string[]
   *   A list of libraries, in the order they should be loaded, including their
   *   dependencies.
   */
  protected function doGetDependencies(array $libraries, array $graph = []) {
    foreach ($libraries as $library) {
      if (!isset($graph[$library])) {
        [$extension, $name] = explode('/', $library, 2);
        $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
        if ($definition) {
          $graph[$library]['edges'] = [];
        }

        if (!empty($definition['dependencies'])) {
          foreach ($definition['dependencies'] as $dependency) {
            $graph[$library]['edges'][$dependency] = $dependency;
          }

          $graph = $this->doGetDependencies($definition['dependencies'], $graph);
        }
      }
    }
    return $graph;
  }

  /**
   * Processes before and after settings for the libraries graph.
   *
   * @param array $graph
   *   The libraries graph array.
   *
   * @return array
   *   The libraries graph with before and after processed.
   */
  protected function doProcessBeforeAfter($graph): array {
    foreach ($graph as $library => $data) {
      [$extension, $name] = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name) + [
        'after' => [],
        'before' => [],
      ];

      foreach ($definition['after'] as $after) {
        if (isset($graph[$after])) {
          $graph[$library]['edges'][$after] = $after;
        }
      }

      foreach ($definition['before'] as $before) {
        if (isset($graph[$before])) {
          $graph[$before]['edges'][$library] = $library;
        }
      }
    }

    return $graph;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinimalRepresentativeSubset(array $libraries) {
    assert(count($libraries) === count(array_unique($libraries)), '$libraries can\'t contain duplicate items.');

    $graph = $this->doGetDependencies($libraries);

    $libraries_to_exclude = [];
    foreach ($graph as $vertex) {
      $libraries_to_exclude += $vertex['edges'];
    }

    return array_values(array_diff($libraries, $libraries_to_exclude));
  }

}
