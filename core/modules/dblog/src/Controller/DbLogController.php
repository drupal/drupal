<?php

namespace Drupal\dblog\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Link;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a DbLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatterInterface $date_formatter, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
  }

  /**
   * Gets an array of log level classes.
   *
   * @return array
   *   An array of log level classes.
   */
  public static function getLogLevelClassMap() {
    return [
      RfcLogLevel::DEBUG => 'dblog-debug',
      RfcLogLevel::INFO => 'dblog-info',
      RfcLogLevel::NOTICE => 'dblog-notice',
      RfcLogLevel::WARNING => 'dblog-warning',
      RfcLogLevel::ERROR => 'dblog-error',
      RfcLogLevel::CRITICAL => 'dblog-critical',
      RfcLogLevel::ALERT => 'dblog-alert',
      RfcLogLevel::EMERGENCY => 'dblog-emergency',
    ];
  }

  /**
   * Displays a listing of database log messages.
   *
   * Messages are truncated at 56 chars.
   * Full-length messages can be viewed on the message details page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @see Drupal\dblog\Form\DblogClearLogConfirmForm
   * @see Drupal\dblog\Controller\DbLogController::eventDetails()
   */
  public function overview(Request $request) {

    $filter = $this->buildFilterQuery($request);
    $rows = [];

    $classes = static::getLogLevelClassMap();

    $this->moduleHandler()->loadInclude('dblog', 'admin.inc');

    $build['dblog_filter_form'] = $this->formBuilder()->getForm('Drupal\dblog\Form\DblogFilterForm');

    $header = [
      // Icon column.
      '',
      [
        'data' => $this->t('Type'),
        'field' => 'w.type',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Date'),
        'field' => 'w.wid',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      $this->t('Message'),
      [
        'data' => $this->t('User'),
        'field' => 'ufd.name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Operations'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $query = $this->database->select('watchdog', 'w')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->fields('w', [
      'wid',
      'uid',
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'link',
    ]);
    $query->leftJoin('users_field_data', 'ufd', '[w].[uid] = [ufd].[uid]');

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
        $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
        $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
        // The link generator will escape any unsafe HTML entities in the final
        // text.
        $message = Link::fromTextAndUrl($log_text, new Url('dblog.event', ['event_id' => $dblog->wid], [
          'attributes' => [
            // Provide a title for the link for useful hover hints. The
            // Attribute object will escape any unsafe HTML entities in the
            // final text.
            'title' => $title,
          ],
        ]))->toString();
      }
      $username = [
        '#theme' => 'username',
        '#account' => $this->userStorage->load($dblog->uid),
      ];
      $rows[] = [
        'data' => [
          // Cells.
          ['class' => ['icon']],
          $this->t($dblog->type),
          $this->dateFormatter->format($dblog->timestamp, 'short'),
          $message,
          ['data' => $username],
          ['data' => ['#markup' => $dblog->link]],
        ],
        // Attributes for table row.
        'class' => [Html::getClass('dblog-' . $dblog->type), $classes[$dblog->severity]],
      ];
    }

    $build['dblog_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'admin-dblog', 'class' => ['admin-dblog']],
      '#empty' => $this->t('No log messages available.'),
      '#attached' => [
        'library' => ['dblog/drupal.dblog'],
      ],
    ];
    $build['dblog_pager'] = ['#type' => 'pager'];

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
   *   format expected by \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If no event found for the given ID.
   */
  public function eventDetails($event_id) {
    $dblog = $this->database->query('SELECT [w].*, [u].[uid] FROM {watchdog} [w] LEFT JOIN {users} [u] ON [u].[uid] = [w].[uid] WHERE [w].[wid] = :id', [':id' => $event_id])->fetchObject();

    if (empty($dblog)) {
      throw new NotFoundHttpException();
    }

    $build = [];
    $severity = RfcLogLevel::getLevels();
    $message = $this->formatMessage($dblog);
    $username = [
      '#theme' => 'username',
      '#account' => $dblog->uid ? $this->userStorage->load($dblog->uid) : User::getAnonymousUser(),
    ];
    $rows = [
      [
        ['data' => $this->t('Type'), 'header' => TRUE],
        $this->t($dblog->type),
      ],
      [
        ['data' => $this->t('Date'), 'header' => TRUE],
        $this->dateFormatter->format($dblog->timestamp, 'long'),
      ],
      [
        ['data' => $this->t('User'), 'header' => TRUE],
        ['data' => $username],
      ],
      [
        ['data' => $this->t('Location'), 'header' => TRUE],
        $this->createLink($dblog->location),
      ],
      [
        ['data' => $this->t('Referrer'), 'header' => TRUE],
        $this->createLink($dblog->referer),
      ],
      [
        ['data' => $this->t('Message'), 'header' => TRUE],
        $message,
      ],
      [
        ['data' => $this->t('Severity'), 'header' => TRUE],
        $severity[$dblog->severity],
      ],
      [
        ['data' => $this->t('Hostname'), 'header' => TRUE],
        $dblog->hostname,
      ],
      [
        ['data' => $this->t('Operations'), 'header' => TRUE],
        ['data' => ['#markup' => $dblog->link]],
      ],
    ];
    if (isset($dblog->backtrace)) {
      $rows[] = [
        ['data' => $this->t('Backtrace'), 'header' => TRUE],
        $dblog->backtrace,
      ];
    }
    $build['dblog_table'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => ['class' => ['dblog-event']],
      '#attached' => [
        'library' => ['dblog/drupal.dblog'],
      ],
    ];

    return $build;
  }

  /**
   * Builds a query for database log administration filters based on session.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array|null
   *   An associative array with keys 'where' and 'args' or NULL if there were
   *   no filters set.
   */
  protected function buildFilterQuery(Request $request) {
    $session_filters = $request->getSession()->get('dblog_overview_filter', []);
    if (empty($session_filters)) {
      return;
    }

    $this->moduleHandler()->loadInclude('dblog', 'admin.inc');

    $filters = dblog_filters();

    // Build query.
    $where = $args = [];
    foreach ($session_filters as $key => $filter) {
      $filter_where = [];
      foreach ($filter as $value) {
        $filter_where[] = $filters[$key]['where'];
        $args[] = $value;
      }
      if (!empty($filter_where)) {
        $where[] = '(' . implode(' OR ', $filter_where) . ')';
      }
    }
    $where = !empty($where) ? implode(' AND ', $where) : '';

    return [
      'where' => $where,
      'args' => $args,
    ];
  }

  /**
   * Formats a database log message.
   *
   * @param object $row
   *   The record from the watchdog table. The object properties are: wid, uid,
   *   severity, type, timestamp, message, variables, link, name.
   *
   *   If the variables contain a @backtrace_string placeholder which is not
   *   used in the message, the formatted backtrace will be assigned to a new
   *   backtrace property on the row object which can be displayed separately.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|false
   *   The formatted log message or FALSE if the message or variables properties
   *   are not set.
   */
  public function formatMessage($row) {
    // Check for required properties.
    if (isset($row->message, $row->variables)) {
      $variables = @unserialize($row->variables);
      // Messages without variables or user specified text.
      if ($variables === NULL) {
        $message = Xss::filterAdmin($row->message);
      }
      elseif (!is_array($variables)) {
        $message = $this->t('Log data is corrupted and cannot be unserialized: @message', ['@message' => Xss::filterAdmin($row->message)]);
      }
      // Message to translate with injected variables.
      else {
        // Ensure backtrace strings are properly formatted.
        if (isset($variables['@backtrace_string'])) {
          $variables['@backtrace_string'] = new FormattableMarkup(
            '<pre class="backtrace">@backtrace_string</pre>', $variables
          );
          // Save a reference so the backtrace can be displayed separately.
          if (!str_contains($row->message, '@backtrace_string')) {
            $row->backtrace = $variables['@backtrace_string'];
          }
        }
        $message = $this->t(Xss::filterAdmin($row->message), $variables);
      }
    }
    else {
      $message = FALSE;
    }
    return $message;
  }

  /**
   * Creates a Link object if the provided URI is valid.
   *
   * @param string|null $uri
   *   The uri string to convert into link if valid.
   *
   * @return \Drupal\Core\Link|string|null
   *   Return a Link object if the uri can be converted as a link. In case of
   *   empty uri or invalid, fallback to the provided $uri.
   */
  protected function createLink($uri) {
    if ($uri !== NULL && UrlHelper::isValid($uri, TRUE)) {
      return new Link($uri, Url::fromUri($uri));
    }
    return $uri;
  }

  /**
   * Shows the most frequent log messages of a given event type.
   *
   * Messages are not truncated on this page because events detailed herein do
   * not have links to a detailed view.
   *
   * @param string $type
   *   Type of database log events to display (e.g., 'search').
   *
   * @return array
   *   A build array in the format expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function topLogMessages($type) {
    $header = [
      ['data' => $this->t('Count'), 'field' => 'count', 'sort' => 'desc'],
      ['data' => $this->t('Message'), 'field' => 'message'],
    ];

    $count_query = $this->database->select('watchdog');
    $count_query->addExpression('COUNT(DISTINCT([message]))');
    $count_query->condition('type', $type);

    $query = $this->database->select('watchdog', 'w')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->addExpression('COUNT([wid])', 'count');
    $query = $query
      ->fields('w', ['message', 'variables'])
      ->condition('w.type', $type)
      ->groupBy('message')
      ->groupBy('variables')
      ->limit(30)
      ->orderByHeader($header);
    $query->setCountQuery($count_query);
    $result = $query->execute();

    $rows = [];
    foreach ($result as $dblog) {
      if ($message = $this->formatMessage($dblog)) {
        $rows[] = [$dblog->count, $message];
      }
    }

    $build['dblog_top_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
      '#attached' => [
        'library' => ['dblog/drupal.dblog'],
      ],
    ];
    $build['dblog_top_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
