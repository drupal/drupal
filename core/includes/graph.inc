<?php

/**
 * @file
 * Directed acyclic graph functions.
 */


/**
 * Perform a depth first sort on a directed acyclic graph.
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
 *
 * @return
 *   The passed-in $graph with more secondary keys filled in:
 *   - 'paths': Contains a list of vertices than can be reached on a path from
 *     this vertex.
 *   - 'reverse_paths': Contains a list of vertices that has a path from them
 *     to this vertex.
 *   - 'weight': If there is a path from a vertex to another then the weight of
 *     the latter is higher.
 *   - 'component': Vertices in the same component have the same component
 *     identifier.
 *
 * @see _drupal_depth_first_search()
 */
function drupal_depth_first_search(&$graph) {
  $state = array(
    // The order of last visit of the depth first search. This is the reverse
    // of the topological order if the graph is acyclic.
    'last_visit_order' => array(),
    // The components of the graph.
    'components' => array(),
  );
  // Perform the actual sort.
  foreach ($graph as $start => $data) {
    _drupal_depth_first_search($graph, $state, $start);
  }

  // We do such a numbering that every component starts with 0. This is useful
  // for module installs as we can install every 0 weighted module in one
  // request, and then every 1 weighted etc.
  $component_weights = array();

  foreach ($state['last_visit_order'] as $vertex) {
    $component = $graph[$vertex]['component'];
    if (!isset($component_weights[$component])) {
      $component_weights[$component] = 0;
    }
    $graph[$vertex]['weight'] = $component_weights[$component]--;
  }
}

/**
 * Helper function to perform a depth first sort.
 *
 * @param $graph
 *   A three dimensional associated graph array.
 * @param $state
 *   An associative array. The key 'last_visit_order' stores a list of the
 *   vertices visited. The key components stores list of vertices belonging
 *   to the same the component.
 * @param $start
 *   An arbitrary vertex where we started traversing the graph.
 * @param $component
 *   The component of the last vertex.
 *
 * @see drupal_depth_first_search()
 */
function _drupal_depth_first_search(&$graph, &$state, $start, &$component = NULL) {
  // Assign new component for each new vertex, i.e. when not called recursively.
  if (!isset($component)) {
    $component = $start;
  }
  // Nothing to do, if we already visited this vertex.
  if (isset($graph[$start]['paths'])) {
    return;
  }
  // Mark $start as visited.
  $graph[$start]['paths'] = array();

  // Assign $start to the current component.
  $graph[$start]['component'] = $component;
  $state['components'][$component][] = $start;

  // Visit edges of $start.
  if (isset($graph[$start]['edges'])) {
    foreach ($graph[$start]['edges'] as $end => $v) {
      // Mark that $start can reach $end.
      $graph[$start]['paths'][$end] = $v;

      if (isset($graph[$end]['component']) && $component != $graph[$end]['component']) {
        // This vertex already has a component, use that from now on and
        // reassign all the previously explored vertices.
        $new_component = $graph[$end]['component'];
        foreach ($state['components'][$component] as $vertex) {
          $graph[$vertex]['component'] = $new_component;
          $state['components'][$new_component][] = $vertex;
        }
        unset($state['components'][$component]);
        $component = $new_component;
      }
      // Only visit existing vertices.
      if (isset($graph[$end])) {
        // Visit the connected vertex.
        _drupal_depth_first_search($graph, $state, $end, $component);

        // All vertices reachable by $end are also reachable by $start.
        $graph[$start]['paths'] += $graph[$end]['paths'];
      }
    }
  }

  // Now that any other subgraph has been explored, add $start to all reverse
  // paths.
  foreach ($graph[$start]['paths'] as $end => $v) {
    if (isset($graph[$end])) {
      $graph[$end]['reverse_paths'][$start] = $v;
    }
  }

  // Record the order of the last visit. This is the reverse of the
  // topological order if the graph is acyclic.
  $state['last_visit_order'][] = $start;
}
