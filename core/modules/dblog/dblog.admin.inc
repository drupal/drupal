<?php

/**
 * @file
 * Administrative page callbacks for the Database Logging module.
 */

/**
 * Page callback: Shows the most frequent log messages of a given event type.
 *
 * Messages are not truncated on this page because events detailed herein do not
 * have links to a detailed view.
 *
 * @param string $type
 *   Type of database log events to display (e.g., 'search').
 *
 * @return array
 *   A build array in the format expected by drupal_render().
 *
 * @see dblog_menu()
 *
 * @deprecated Use \Drupal\dblog\Controller\DblogController::pageNotFound(),
 *   \Drupal\dblog\Controller\DblogController::accessDenied(), or
 *   \Drupal\dblog\Controller\DblogController::search()
 */
function dblog_top($type) {

  $header = array(
    array('data' => t('Count'), 'field' => 'count', 'sort' => 'desc'),
    array('data' => t('Message'), 'field' => 'message')
  );
  $count_query = db_select('watchdog');
  $count_query->addExpression('COUNT(DISTINCT(message))');
  $count_query->condition('type', $type);

  $query = db_select('watchdog', 'w')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender');
  $query->addExpression('COUNT(wid)', 'count');
  $query = $query
    ->fields('w', array('message', 'variables'))
    ->condition('w.type', $type)
    ->groupBy('message')
    ->groupBy('variables')
    ->limit(30)
    ->orderByHeader($header);
  $query->setCountQuery($count_query);
  $result = $query->execute();

  $rows = array();
  foreach ($result as $dblog) {
    // Check for required properties.
    if (isset($dblog->message) && isset($dblog->variables)) {
      // Messages without variables or user specified text.
      if ($dblog->variables === 'N;') {
        $message = $dblog->message;
      }
      // Message to translate with injected variables.
      else {
        $message = t($dblog->message, unserialize($dblog->variables));
      }
    }
    $rows[] = array($dblog->count, $message);
  }

  $build['dblog_top_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No log messages available.'),
  );
  $build['dblog_top_pager'] = array('#theme' => 'pager');

  return $build;
}

/**
 * Creates a list of database log administration filters that can be applied.
 *
 * @return array
 *   Associative array of filters. The top-level keys are used as the form
 *   element names for the filters, and the values are arrays with the following
 *   elements:
 *   - title: Title of the filter.
 *   - where: The filter condition.
 *   - options: Array of options for the select list for the filter.
 */
function dblog_filters() {
  $filters = array();

  foreach (_dblog_get_message_types() as $type) {
    $types[$type] = t($type);
  }

  if (!empty($types)) {
    $filters['type'] = array(
      'title' => t('Type'),
      'where' => "w.type = ?",
      'options' => $types,
    );
  }

  $filters['severity'] = array(
    'title' => t('Severity'),
    'where' => 'w.severity = ?',
    'options' => watchdog_severity_levels(),
  );

  return $filters;
}
