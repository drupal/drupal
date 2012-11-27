<?php

/**
 * @file
 * Tests Operations.
 */

/**
 * Page callback for manual testing of operation links.
 */
function design_test_page_list_operations() {
  $build = array(
    '#theme' => 'table',
    '#header' => array('Label', 'Created', 'Operations'),
    '#rows' => array(),
  );
  // Add an item with a very long label, using common operations.
  $build['#rows']['long']['label'] = l('An item with a very insanely long label, which offers quite a couple of common operations', 'item/long');
  $build['#rows']['long']['created'] = format_interval(3200);
  $build['#rows']['long']['operations'] = array(
    'data' => array(
      '#type' => 'operations',
      '#subtype' => 'node',
      '#links' => array(
        'edit' => array(
          'title' => 'edit',
          'href' => 'item/long/edit',
        ),
        'disable' => array(
          'title' => 'disable',
          'href' => 'item/long/disable',
        ),
        'clone' => array(
          'title' => 'clone',
          'href' => 'item/long/clone',
        ),
        'delete' => array(
          'title' => 'delete',
          'href' => 'item/long/delete',
        ),
      ),
    ),
  );

  // Add another item, using common operations.
  $build['#rows']['another']['label'] = l('Another item, using common operations', 'item/another');
  $build['#rows']['another']['created'] = format_interval(8600);
  $build['#rows']['another']['operations'] = $build['#rows']['long']['operations'];

  // Add an item with only one operation.
  $build['#rows']['one']['label'] = l('An item with only one operation', 'item/one');
  $build['#rows']['one']['created'] = format_interval(12400);
  $build['#rows']['one']['operations'] = array(
    'data' => array(
      '#type' => 'operations',
      '#subtype' => 'node',
      '#links' => array(
        'edit' => array(
          'title' => 'edit',
          'href' => 'item/long/edit',
        ),
      ),
    ),
  );

  // Add an item that can only be viewed.
  $build['#rows']['view']['label'] = l('An item that can only be viewed', 'item/view');
  $build['#rows']['view']['created'] = format_interval(12400);
  $build['#rows']['view']['operations'] = array(
    'data' => array(
      '#type' => 'operations',
      '#subtype' => 'node',
      '#links' => array(),
    ),
  );

  // Add an item for which the default operation is denied.
  $build['#rows']['denied']['label'] = l('An item for which the default operation is denied', 'item/denied');
  $build['#rows']['denied']['created'] = format_interval(18600);
  $build['#rows']['denied']['operations'] = $build['#rows']['long']['operations'];
  unset($build['#rows']['denied']['operations']['data']['#links']['edit']);

  return $build;
}
