<?php

/**
 * @file
 * Contains \Drupal\dblog\Controller\DbLogController.
 */

namespace Drupal\dblog\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for dblog routes.
 */
class DbLogController implements ControllerInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler')
    );
  }

  /**
   * Constructs a DbLogController object.
   *
   * @param Connection $database
   *   A database connection.
   * @param ModuleHandlerInterface $module_handler
   *   A module handler.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gets an array of log level classes.
   *
   * @return array
   *   An array of log level classes.
   */
  public static function getLogLevelClassMap() {
    return array(
      WATCHDOG_DEBUG => 'dblog-debug',
      WATCHDOG_INFO => 'dblog-info',
      WATCHDOG_NOTICE => 'dblog-notice',
      WATCHDOG_WARNING => 'dblog-warning',
      WATCHDOG_ERROR => 'dblog-error',
      WATCHDOG_CRITICAL => 'dblog-critical',
      WATCHDOG_ALERT => 'dblog-alert',
      WATCHDOG_EMERGENCY => 'dblog-emergency',
    );
  }

  /**
   * Displays a listing of database log messages.
   *
   * Messages are truncated at 56 chars.
   * Full-length messages can be viewed on the message details page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @see dblog_clear_log_form()
   * @see dblog_event()
   * @see dblog_filter_form()
   */
  public function overview() {

    $filter = $this->buildFilterQuery();
    $rows = array();

    $classes = static::getLogLevelClassMap();

    $this->moduleHandler->loadInclude('dblog', 'admin.inc');

    $build['dblog_filter_form'] = drupal_get_form('dblog_filter_form');
    $build['dblog_clear_log_form'] = drupal_get_form('dblog_clear_log_form');

    $header = array(
      // Icon column.
      '',
      array(
        'data' => t('Type'),
        'field' => 'w.type',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM)),
      array(
        'data' => t('Date'),
        'field' => 'w.wid',
        'sort' => 'desc',
        'class' => array(RESPONSIVE_PRIORITY_LOW)),
      t('Message'),
      array(
        'data' => t('User'),
        'field' => 'u.name',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM)),
      array(
        'data' => t('Operations'),
        'class' => array(RESPONSIVE_PRIORITY_LOW)),
    );

    $query = $this->database->select('watchdog', 'w')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('w', array(
      'wid',
      'uid',
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'link',
    ));

    if (!empty($filter['where'])) {
      $query->where($filter['where'], $filter['args']);
    }
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

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
        if (isset($dblog->wid)) {
          // Truncate link_text to 56 chars of message.
          $log_text = Unicode::truncate(filter_xss($message, array()), 56, TRUE, TRUE);
          $message = l($log_text, 'admin/reports/event/' . $dblog->wid, array('html' => TRUE));
        }
      }
      $username = array(
        '#theme' => 'username',
        '#account' => user_load($dblog->uid),
      );
      $rows[] = array(
        'data' => array(
          // Cells.
          array('class' => array('icon')),
          t($dblog->type),
          format_date($dblog->timestamp, 'short'),
          $message,
          array('data' => $username),
          filter_xss($dblog->link),
        ),
        // Attributes for table row.
        'class' => array(drupal_html_class('dblog-' . $dblog->type), $classes[$dblog->severity]),
      );
    }

    $build['dblog_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => 'admin-dblog', 'class' => array('admin-dblog')),
      '#empty' => t('No log messages available.'),
    );
    $build['dblog_pager'] = array('#theme' => 'pager');

    return $build;

  }

  /**
   * Builds a query for database log administration filters based on session.
   *
   * @return array
   *   An associative array with keys 'where' and 'args'.
   */
  protected function buildFilterQuery() {
    if (empty($_SESSION['dblog_overview_filter'])) {
      return;
    }

    $this->moduleHandler->loadInclude('dblog', 'admin.inc');

    $filters = dblog_filters();

    // Build query.
    $where = $args = array();
    foreach ($_SESSION['dblog_overview_filter'] as $key => $filter) {
      $filter_where = array();
      foreach ($filter as $value) {
        $filter_where[] = $filters[$key]['where'];
        $args[] = $value;
      }
      if (!empty($filter_where)) {
        $where[] = '(' . implode(' OR ', $filter_where) . ')';
      }
    }
    $where = !empty($where) ? implode(' AND ', $where) : '';

    return array(
      'where' => $where,
      'args' => $args,
    );
  }

}
