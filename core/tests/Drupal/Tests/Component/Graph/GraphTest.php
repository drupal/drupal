<?php

namespace Drupal\Tests\Component\Graph;

use Drupal\Component\Graph\Graph;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Graph\Graph
 * @group Graph
 */
class GraphTest extends TestCase {

  /**
   * Test depth-first-search features.
   */
  public function testDepthFirstSearch() {
    // The sample graph used is:
    // 1 --> 2 --> 3     5 ---> 6
    //       |     ^     ^
    //       |     |     |
    //       |     |     |
    //       +---> 4 <-- 7      8 ---> 9
    $graph = $this->normalizeGraph([
      1 => [2],
      2 => [3, 4],
      3 => [],
      4 => [3],
      5 => [6],
      7 => [4, 5],
      8 => [9],
      9 => [],
    ]);
    $graph_object = new Graph($graph);
    $graph = $graph_object->searchAndSort();

    $expected_paths = [
      1 => [2, 3, 4],
      2 => [3, 4],
      3 => [],
      4 => [3],
      5 => [6],
      7 => [4, 3, 5, 6],
      8 => [9],
      9 => [],
    ];
    $this->assertPaths($graph, $expected_paths);

    $expected_reverse_paths = [
      1 => [],
      2 => [1],
      3 => [2, 1, 4, 7],
      4 => [2, 1, 7],
      5 => [7],
      7 => [],
      8 => [],
      9 => [8],
    ];
    $this->assertReversePaths($graph, $expected_reverse_paths);

    // Assert that DFS didn't created "missing" vertexes automatically.
    $this->assertFalse(isset($graph[6]), 'Vertex 6 has not been created');

    $expected_components = [
      [1, 2, 3, 4, 5, 7],
      [8, 9],
    ];
    $this->assertComponents($graph, $expected_components);

    $expected_weights = [
      [1, 2, 3],
      [2, 4, 3],
      [7, 4, 3],
      [7, 5],
      [8, 9],
    ];
    $this->assertWeights($graph, $expected_weights);
  }

  /**
   * Normalizes a graph.
   *
   * @param $graph
   *   A graph array processed by \Drupal\Component\Graph\Graph::searchAndSort()
   *
   * @return array
   *   The normalized version of a graph.
   */
  protected function normalizeGraph($graph) {
    $normalized_graph = [];
    foreach ($graph as $vertex => $edges) {
      // Create vertex even if it hasn't any edges.
      $normalized_graph[$vertex] = [];
      foreach ($edges as $edge) {
        $normalized_graph[$vertex]['edges'][$edge] = TRUE;
      }
    }
    return $normalized_graph;
  }

  /**
   * Verify expected paths in a graph.
   *
   * @param $graph
   *   A graph array processed by \Drupal\Component\Graph\Graph::searchAndSort()
   * @param $expected_paths
   *   An associative array containing vertices with their expected paths.
   */
  protected function assertPaths($graph, $expected_paths) {
    foreach ($expected_paths as $vertex => $paths) {
      // Build an array with keys = $paths and values = TRUE.
      $expected = array_fill_keys($paths, TRUE);
      $result = isset($graph[$vertex]['paths']) ? $graph[$vertex]['paths'] : [];
      $this->assertEquals($expected, $result, sprintf('Expected paths for vertex %s: %s, got %s', $vertex, $this->displayArray($expected, TRUE), $this->displayArray($result, TRUE)));
    }
  }

  /**
   * Verify expected reverse paths in a graph.
   *
   * @param $graph
   *   A graph array processed by \Drupal\Component\Graph\Graph::searchAndSort()
   * @param $expected_reverse_paths
   *   An associative array containing vertices with their expected reverse
   *   paths.
   */
  protected function assertReversePaths($graph, $expected_reverse_paths) {
    foreach ($expected_reverse_paths as $vertex => $paths) {
      // Build an array with keys = $paths and values = TRUE.
      $expected = array_fill_keys($paths, TRUE);
      $result = isset($graph[$vertex]['reverse_paths']) ? $graph[$vertex]['reverse_paths'] : [];
      $this->assertEquals($expected, $result, sprintf('Expected reverse paths for vertex %s: %s, got %s', $vertex, $this->displayArray($expected, TRUE), $this->displayArray($result, TRUE)));
    }
  }

  /**
   * Verify expected components in a graph.
   *
   * @param $graph
   *   A graph array processed by \Drupal\Component\Graph\Graph::searchAndSort().
   * @param $expected_components
   *   An array containing of components defined as a list of their vertices.
   */
  protected function assertComponents($graph, $expected_components) {
    $unassigned_vertices = array_fill_keys(array_keys($graph), TRUE);
    foreach ($expected_components as $component) {
      $result_components = [];
      foreach ($component as $vertex) {
        $result_components[] = $graph[$vertex]['component'];
        unset($unassigned_vertices[$vertex]);
      }
      $this->assertCount(1, array_unique($result_components), sprintf('Expected one unique component for vertices %s, got %s', $this->displayArray($component), $this->displayArray($result_components)));
    }
    $this->assertEquals([], $unassigned_vertices, sprintf('Vertices not assigned to a component: %s', $this->displayArray($unassigned_vertices, TRUE)));
  }

  /**
   * Verify expected order in a graph.
   *
   * @param $graph
   *   A graph array processed by \Drupal\Component\Graph\Graph::searchAndSort()
   * @param $expected_orders
   *   An array containing lists of vertices in their expected order.
   */
  protected function assertWeights($graph, $expected_orders) {
    foreach ($expected_orders as $order) {
      $previous_vertex = array_shift($order);
      foreach ($order as $vertex) {
        $this->assertTrue($graph[$previous_vertex]['weight'] < $graph[$vertex]['weight'], sprintf('Weights of %s and %s are correct relative to each other', $previous_vertex, $vertex));
      }
    }
  }

  /**
   * Helper function to output vertices as comma-separated list.
   *
   * @param $paths
   *   An array containing a list of vertices.
   * @param $keys
   *   (optional) Whether to output the keys of $paths instead of the values.
   */
  protected function displayArray($paths, $keys = FALSE) {
    if (!empty($paths)) {
      return implode(', ', $keys ? array_keys($paths) : $paths);
    }
    else {
      return '(empty)';
    }
  }

}
