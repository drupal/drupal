<?php

/**
 * @file
 * Contains \Drupal\dblog\Controller\DbLogController.
 */

namespace Drupal\dblog\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for dblog routes.
 */
class DbLogController extends ControllerBase {

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a DbLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatter $date_formatter, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
  }

  /**
   * Gets an array of log level classes.
   *
   * @return array
   *   An array of log level classes.
   */
  public static function getLogLevelClassMap() {
    return array(
      RfcLogLevel::DEBUG => 'dblog-debug',
      RfcLogLevel::INFO => 'dblog-info',
      RfcLogLevel::NOTICE => 'dblog-notice',
      RfcLogLevel::WARNING => 'dblog-warning',
      RfcLogLevel::ERROR => 'dblog-error',
      RfcLogLevel::CRITICAL => 'dblog-critical',
      RfcLogLevel::ALERT => 'dblog-alert',
      RfcLogLevel::EMERGENCY => 'dblog-emergency',
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
   */
  public function overview() {

    $filter = $this->buildFilterQuery();
    $rows = array();

    $classes = static::getLogLevelClassMap();

    $this->moduleHandler->loadInclude('dblog', 'admin.inc');

    $build['dblog_filter_form'] = $this->formBuilder->getForm('Drupal\dblog\Form\DblogFilterForm');
    $build['dblog_clear_log_form'] = $this->formBuilder->getForm('Drupal\dblog\Form\DblogClearLogForm');

    $header = array(
      // Icon column.
      '',
      array(
        'data' => $this->t('Type'),
        'field' => 'w.type',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM)),
      array(
        'data' => $this->t('Date'),
        'field' => 'w.wid',
        'sort' => 'desc',
        'class' => array(RESPONSIVE_PRIORITY_LOW)),
      $this->t('Message'),
      array(
        'data' => $this->t('User'),
        'field' => 'ufd.name',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM)),
      array(
        'data' => $this->t('Operations'),
        'class' => array(RESPONSIVE_PRIORITY_LOW)),
    );

    $query = $this->database->select('watchdog', 'w')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
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
    $query->leftJoin('users_field_data', 'ufd', 'w.uid = ufd.uid');

    if (!empty($filter['where'])) {
      $query->where($filter['where'], $filter['args']);
    }
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $dblog) {
      $message = $this->formatMessage($dblog);
      if ($message && isset($dblog->wid)) {
        // Truncate link_text to 56 chars of message.
        $log_text = Unicode::truncate(Xss::filter($message, array()), 56, TRUE, TRUE);
        $message = $this->l($log_text, new Url('dblog.event', array('event_id' => $dblog->wid), array(
          'attributes' => array(
            // Provide a title for the link for useful hover hints.
            'title' => Unicode::truncate(strip_tags($message), 256, TRUE, TRUE),
          ),
          'html' => TRUE,
        )));
      }
      $username = array(
        '#theme' => 'username',
        '#account' => user_load($dblog->uid),
      );
      $rows[] = array(
        'data' => array(
          // Cells.
          array('class' => array('icon')),
          $this->t($dblog->type),
          $this->dateFormatter->format($dblog->timestamp, 'short'),
          $message,
          array('data' => $username),
          Xss::filter($dblog->link),
        ),
        // Attributes for table row.
        'class' => array(drupal_html_class('dblog-' . $dblog->type), $classes[$dblog->severity]),
      );
    }

    $build['dblog_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => 'admin-dblog', 'class' => array('admin-dblog')),
      '#empty' => $this->t('No log messages available.'),
      '#attached' => array(
        'library' => array('dblog/drupal.dblog'),
      ),
    );
    $build['dblog_pager'] = array('#theme' => 'pager');

    return $build;

  }

  /**
   * Displays details about a specific database log message.
   *
   * @param int $event_id
   *   Unique ID of the database log message.
   *
   * @return array
   *   If the ID is located in the Database Logging table, a build array in the
   *   format expected by drupal_render();
   *
   */
  public function eventDetails($event_id) {
    $build = array();
    if ($dblog = $this->database->query('SELECT w.*, u.name, u.uid FROM {watchdog} w INNER JOIN {users_field_data} u ON w.uid = u.uid WHERE w.wid = :id AND u.default_langcode = 1', array(':id' => $event_id))->fetchObject()) {
      $severity = RfcLogLevel::getLevels();
      $message = $this->formatMessage($dblog);
      $username = array(
        '#theme' => 'username',
        '#account' => user_load($dblog->uid),
      );
      $rows = array(
        array(
          array('data' => $this->t('Type'), 'header' => TRUE),
          $this->t($dblog->type),
        ),
        array(
          array('data' => $this->t('Date'), 'header' => TRUE),
          $this->dateFormatter->format($dblog->timestamp, 'long'),
        ),
        array(
          array('data' => $this->t('User'), 'header' => TRUE),
          array('data' => $username),
        ),
        array(
          array('data' => $this->t('Location'), 'header' => TRUE),
          _l($dblog->location, $dblog->location),
        ),
        array(
          array('data' => $this->t('Referrer'), 'header' => TRUE),
          _l($dblog->referer, $dblog->referer),
        ),
        array(
          array('data' => $this->t('Message'), 'header' => TRUE),
          $message,
        ),
        array(
          array('data' => $this->t('Severity'), 'header' => TRUE),
          $severity[$dblog->severity],
        ),
        array(
          array('data' => $this->t('Hostname'), 'header' => TRUE),
          String::checkPlain($dblog->hostname),
        ),
        array(
          array('data' => $this->t('Operations'), 'header' => TRUE),
          $dblog->link,
        ),
      );
      $build['dblog_table'] = array(
        '#type' => 'table',
        '#rows' => $rows,
        '#attributes' => array('class' => array('dblog-event')),
        '#attached' => array(
          'library' => array('dblog/drupal.dblog'),
        ),
      );
    }

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

  /**
   * Formats a database log message.
   *
   * @param stdClass $row
   *   The record from the watchdog table. The object properties are: wid, uid,
   *   severity, type, timestamp, message, variables, link, name.
   *
   * @return string|false
   *   The formatted log message or FALSE if the message or variables properties
   *   are not set.
   */
  public function formatMessage($row) {
    // Check for required properties.
    if (isset($row->message) && isset($row->variables)) {
      // Messages without variables or user specified text.
      if ($row->variables === 'N;') {
        $message = $row->message;
      }
      // Message to translate with injected variables.
      else {
        $message = $this->t($row->message, unserialize($row->variables));
      }
    }
    else {
      $message = FALSE;
    }
    return $message;
  }

  /**
   * Shows the most frequent log messages of a given event type.
   *
   * Messages are not truncated on this page because events detailed herein do
   * not have links to a detailed view.
   *
   * Use one of the above *Report() methods.
   *
   * @param string $type
   *   Type of database log events to display (e.g., 'search').
   *
   * @return array
   *   A build array in the format expected by drupal_render().
   */
  public function topLogMessages($type) {
    $header = array(
      array('data' => $this->t('Count'), 'field' => 'count', 'sort' => 'desc'),
      array('data' => $this->t('Message'), 'field' => 'message'),
    );

    $count_query = $this->database->select('watchdog');
    $count_query->addExpression('COUNT(DISTINCT(message))');
    $count_query->condition('type', $type);

    $query = $this->database->select('watchdog', 'w')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
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
      if ($message = $this->formatMessage($dblog)) {
        $rows[] = array($dblog->count, $message);
      }
    }

    $build['dblog_top_table']  = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
      '#attached' => array(
        'library' => array('dblog/drupal.dblog'),
      ),
    );
    $build['dblog_top_pager'] = array('#theme' => 'pager');

    return $build;
  }

}
