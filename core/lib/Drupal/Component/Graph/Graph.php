<?php

/**
 * @file
 * Definition of Drupal\Component\Graph\Graph.
 */

namespace Drupal\Component\Graph;

/**
 * Directed acyclic graph manipulation.
 */
class Graph {

  /**
   * Holds the directed acyclic graph.
   */
  protected $graph;

  /**
   * Instantiates the depth first search object.
   *
   * @param $graph
   *   A three dimensional associated array, with the first keys being the names
   *   of the vertices, these can be strings or numbers. The second key is
   *   'edges' and the third one are again vertices, each such key representing
   *   an edge. Values of array elements are copied over.
   *
   *   Example:
   *   @code
   *     $graph[1]['edges'][2] = 1;
   *     $graph[2]['edges'][3] = 1;
   *     $graph[2]['edges'][4] = 1;
   *     $graph[3]['edges'][4] = 1;
   *   @endcode
   *
   *   On return you will also have:
   *   @code
   *     $graph[1]['paths'][2] = 1;
   *     $graph[1]['paths'][3] = 1;
   *     $graph[2]['reverse_paths'][1] = 1;
   *     $graph[3]['reverse_paths'][1] = 1;
   *   @endcode
   */
  public function __construct($graph) {
    $this->graph = $graph;
  }

  /**
   * Performs a depth-first search and sort on the directed acyclic graph.
   *
   * @return
   *   The given $graph with more secondary keys filled in:
   *   - 'paths': Contains a list of vertices than can be reached on a path from
   *     this vertex.
   *   - 'reverse_paths': Contains a list of vertices that has a path from them
   *     to this vertex.
   *   - 'weight': If there is a path from a vertex to another then the weight of
   *     the latter is higher.
   *   - 'component': Vertices in the same component have the same component
   *     identifier.
   */
  public function searchAndSort() {
    $state = array(
      // The order of last visit of the depth first search. This is the reverse
      // of the topological order if the graph is acyclic.
      'last_visit_order' => array(),
      // The components of the graph.
      'components' => array(),
    );
    // Perform the actual search.
    foreach ($this->graph as $start => $data) {
      $this->depthFirstSearch($state, $start);
    }

    // We do such a numbering that every component starts with 0. This is useful
    // for module installs as we can install every 0 weighted module in one
    // request, and then every 1 weighted etc.
    $component_weights = array();

    foreach ($state['last_visit_order'] as $vertex) {
      $component = $this->graph[$vertex]['component'];
      if (!isset($component_weights[$component])) {
        $component_weights[$component] = 0;
      }
      $this->graph[$vertex]['weight'] = $component_weights[$component]--;
    }

    return $this->graph;
  }

  /**
   * Performs a depth-first search on a graph.
   *
   * @param $state
   *   An associative array. The key 'last_visit_order' stores a list of the
   *   vertices visited. The key components stores list of vertices belonging
   *   to the same the component.
   * @param $start
   *   An arbitrary vertex where we started traversing the graph.
   * @param $component
   *   The component of the last vertex.
   *
   * @see \Drupal\Component\Graph\Graph::searchAndSort()
   */
  protected function depthFirstSearch(&$state, $start, &$component = NULL) {
    // Assign new component for each new vertex, i.e. when not called recursively.
    if (!isset($component)) {
      $component = $start;
    }
    // Nothing to do, if we already visited this vertex.
    if (isset($this->graph[$start]['paths'])) {
      return;
    }
    // Mark $start as visited.
    $this->graph[$start]['paths'] = array();

    // Assign $start to the current component.
    $this->graph[$start]['component'] = $component;
    $state['components'][$component][] = $start;

    // Visit edges of $start.
    if (isset($this->graph[$start]['edges'])) {
      foreach ($this->graph[$start]['edges'] as $end => $v) {
        // Mark that $start can reach $end.
        $this->graph[$start]['paths'][$end] = $v;

        if (isset($this->graph[$end]['component']) && $component != $this->graph[$end]['component']) {
          // This vertex already has a component, use that from now on and
          // reassign all the previously explored vertices.
          $new_component = $this->graph[$end]['component'];
          foreach ($state['components'][$component] as $vertex) {
            $this->graph[$vertex]['component'] = $new_component;
            $state['components'][$new_component][] = $vertex;
          }
          unset($state['components'][$component]);
          $component = $new_component;
        }
        // Only visit existing vertices.
        if (isset($this->graph[$end])) {
          // Visit the connected vertex.
          $this->depthFirstSearch($state, $end, $component);

          // All vertices reachable by $end are also reachable by $start.
          $this->graph[$start]['paths'] += $this->graph[$end]['paths'];
        }
      }
    }

    // Now that any other subgraph has been explored, add $start to all reverse
    // paths.
    foreach ($this->graph[$start]['paths'] as $end => $v) {
      if (isset($this->graph[$end])) {
        $this->graph[$end]['reverse_paths'][$start] = $v;
      }
    }

    // Record the order of the last visit. This is the reverse of the
    // topological order if the graph is acyclic.
    $state['last_visit_order'][] = $start;
  }
}
