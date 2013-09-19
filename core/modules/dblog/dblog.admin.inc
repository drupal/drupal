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

/**
 * Form constructor for the database logging filter form.
 *
 * @see dblog_filter_form_validate()
 * @see dblog_filter_form_submit()
 * @see dblog_overview()
 *
 * @ingroup forms
 */
function dblog_filter_form($form) {
  $filters = dblog_filters();

  $form['filters'] = array(
    '#type' => 'details',
    '#title' => t('Filter log messages'),
    '#collapsed' => empty($_SESSION['dblog_overview_filter']),
  );
  foreach ($filters as $key => $filter) {
    $form['filters']['status'][$key] = array(
      '#title' => $filter['title'],
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 8,
      '#options' => $filter['options'],
    );
    if (!empty($_SESSION['dblog_overview_filter'][$key])) {
      $form['filters']['status'][$key]['#default_value'] = $_SESSION['dblog_overview_filter'][$key];
    }
  }

  $form['filters']['actions'] = array(
    '#type' => 'actions',
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['filters']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
  );
  if (!empty($_SESSION['dblog_overview_filter'])) {
    $form['filters']['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset')
    );
  }
  return $form;
}

/**
 * Form validation handler for dblog_filter_form().
 *
 * @see dblog_filter_form_submit()
 */
function dblog_filter_form_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Filter') && empty($form_state['values']['type']) && empty($form_state['values']['severity'])) {
    form_set_error('type', t('You must select something to filter by.'));
  }
}

/**
 * Form submission handler for dblog_filter_form().
 *
 * @see dblog_filter_form_validate()
 */
function dblog_filter_form_submit($form, &$form_state) {
  $op = $form_state['values']['op'];
  $filters = dblog_filters();
  switch ($op) {
    case t('Filter'):
      foreach ($filters as $name => $filter) {
        if (isset($form_state['values'][$name])) {
          $_SESSION['dblog_overview_filter'][$name] = $form_state['values'][$name];
        }
      }
      break;
    case t('Reset'):
      $_SESSION['dblog_overview_filter'] = array();
      break;
  }
  return 'admin/reports/dblog';
}

/**
 * Form constructor for the form that clears out the log.
 *
 * @see dblog_clear_log_submit()
 * @ingroup forms
 */
function dblog_clear_log_form($form) {
  $form['dblog_clear'] = array(
    '#type' => 'details',
    '#title' => t('Clear log messages'),
    '#description' => t('This will permanently remove the log messages from the database.'),
    '#collapsed' => TRUE,
  );
  $form['dblog_clear']['clear'] = array(
    '#type' => 'submit',
    '#value' => t('Clear log messages'),
    '#submit' => array('dblog_clear_log_submit'),
  );
  return $form;
}

/**
 * Form submission handler for dblog_clear_log_form().
 */
function dblog_clear_log_submit() {
  $_SESSION['dblog_overview_filter'] = array();
  db_delete('watchdog')->execute();
  drupal_set_message(t('Database log cleared.'));
}
