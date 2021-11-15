<?php

namespace Drupal\system\Controller;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Update\UpdateRegistry;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for database update routes.
 */
class DbUpdateController extends ControllerBase {

  /**
   * The keyvalue expirable factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueExpirableFactory;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The bare HTML page renderer.
   *
   * @var \Drupal\Core\Render\BareHtmlPageRendererInterface
   */
  protected $bareHtmlPageRenderer;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The post update registry.
   *
   * @var \Drupal\Core\Update\UpdateRegistry
   */
  protected $postUpdateRegistry;

  /**
   * Constructs a new UpdateController.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable_factory
   *   The keyvalue expirable factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend interface.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Render\BareHtmlPageRendererInterface $bare_html_page_renderer
   *   The bare HTML page renderer.
   * @param \Drupal\Core\Update\UpdateRegistry $post_update_registry
   *   The post update registry.
   */
  public function __construct($root, KeyValueExpirableFactoryInterface $key_value_expirable_factory, CacheBackendInterface $cache, StateInterface $state, ModuleHandlerInterface $module_handler, AccountInterface $account, BareHtmlPageRendererInterface $bare_html_page_renderer, UpdateRegistry $post_update_registry) {
    $this->root = $root;
    $this->keyValueExpirableFactory = $key_value_expirable_factory;
    $this->cache = $cache;
    $this->state = $state;
    $this->moduleHandler = $module_handler;
    $this->account = $account;
    $this->bareHtmlPageRenderer = $bare_html_page_renderer;
    $this->postUpdateRegistry = $post_update_registry;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('app.root'),
      $container->get('keyvalue.expirable'),
      $container->get('cache.default'),
      $container->get('state'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('bare_html_page_renderer'),
      $container->get('update.post_update_registry')
    );
  }

  /**
   * Returns a database update page.
   *
   * @param string $op
   *   The update operation to perform. Can be any of the below:
   *    - info
   *    - selection
   *    - run
   *    - results
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object object.
   */
  public function handle($op, Request $request) {
    require_once $this->root . '/core/includes/install.inc';
    require_once $this->root . '/core/includes/update.inc';

    drupal_load_updates();

    if ($request->query->get('continue')) {
      $request->getSession()->set('update_ignore_warnings', TRUE);
    }

    $regions = [];
    $requirements = update_check_requirements();
    $severity = drupal_requirements_severity($requirements);
    if ($severity == REQUIREMENT_ERROR || ($severity == REQUIREMENT_WARNING && !$request->getSession()->has('update_ignore_warnings'))) {
      $regions['sidebar_first'] = $this->updateTasksList('requirements');
      $output = $this->requirements($severity, $requirements, $request);
    }
    else {
      switch ($op) {
        case 'selection':
          $regions['sidebar_first'] = $this->updateTasksList('selection');
          $output = $this->selection($request);
          break;

        case 'run':
          $regions['sidebar_first'] = $this->updateTasksList('run');
          $output = $this->triggerBatch($request);
          break;

        case 'info':
          $regions['sidebar_first'] = $this->updateTasksList('info');
          $output = $this->info($request);
          break;

        case 'results':
          $regions['sidebar_first'] = $this->updateTasksList('results');
          $output = $this->results($request);
          break;

        // Regular batch ops : defer to batch processing API.
        default:
          require_once $this->root . '/core/includes/batch.inc';
          $regions['sidebar_first'] = $this->updateTasksList('run');
          $output = _batch_page($request);
          break;
      }
    }

    if ($output instanceof Response) {
      return $output;
    }
    $title = $output['#title'] ?? $this->t('Drupal database update');

    return $this->bareHtmlPageRenderer->renderBarePage($output, $title, 'maintenance_page', $regions);
  }

  /**
   * Returns the info database update page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array.
   */
  protected function info(Request $request) {
    // Change query-strings on css/js files to enforce reload for all users.
    _drupal_flush_css_js();
    // Flush the cache of all data for the update status module.
    $this->keyValueExpirableFactory->get('update')->deleteAll();
    $this->keyValueExpirableFactory->get('update_available_release')->deleteAll();

    $build['info_header'] = [
      '#markup' => '<p>' . $this->t('Use this utility to update your database whenever a new release of Drupal or a module is installed.') . '</p><p>' . $this->t('For more detailed information, see the <a href="https://www.drupal.org/docs/updating-drupal">Updating Drupal guide</a>. If you are unsure what these terms mean you should probably contact your hosting provider.') . '</p>',
    ];

    $info[] = $this->t("<strong>Back up your code</strong>. Hint: when backing up module code, do not leave that backup in the 'modules' or 'sites/*/modules' directories as this may confuse Drupal's auto-discovery mechanism.");
    // @todo Simplify with https://www.drupal.org/node/2548095
    $base_url = str_replace('/update.php', '', $request->getBaseUrl());
    $info[] = $this->t('Put your site into <a href=":url">maintenance mode</a>.', [
      ':url' => Url::fromRoute('system.site_maintenance_mode')->setOption('base_url', $base_url)->toString(TRUE)->getGeneratedUrl(),
    ]);
    $info[] = $this->t('<strong>Back up your database</strong>. This process will change your database values and in case of emergency you may need to revert to a backup.');
    $info[] = $this->t('Install your new files in the appropriate location, as described in the handbook.');
    $build['info'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => $info,
    ];
    $build['info_footer'] = [
      '#markup' => '<p>' . $this->t('When you have performed the steps above, you may proceed.') . '</p>',
    ];

    $build['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Continue'),
      '#attributes' => ['class' => ['button', 'button--primary']],
      // @todo Revisit once https://www.drupal.org/node/2548095 is in.
      '#url' => Url::fromUri('base://selection'),
    ];
    return $build;
  }

  /**
   * Renders a list of available database updates.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array.
   */
  protected function selection(Request $request) {
    // Make sure there is no stale theme registry.
    $this->cache->deleteAll();

    $count = 0;
    $incompatible_count = 0;
    $build['start'] = [
      '#tree' => TRUE,
      '#type' => 'details',
    ];

    // Ensure system.module's updates appear first.
    $build['start']['system'] = [];

    $starting_updates = [];
    $incompatible_updates_exist = FALSE;
    $updates_per_module = [];
    foreach (['update', 'post_update'] as $update_type) {
      switch ($update_type) {
        case 'update':
          $updates = update_get_update_list();
          break;

        case 'post_update':
          $updates = $this->postUpdateRegistry->getPendingUpdateInformation();
          break;
      }
      foreach ($updates as $module => $update) {
        if (!isset($update['start'])) {
          $build['start'][$module] = [
            '#type' => 'item',
            '#title' => $module . ' module',
            '#markup' => $update['warning'],
            '#prefix' => '<div class="messages messages--warning">',
            '#suffix' => '</div>',
          ];
          $incompatible_updates_exist = TRUE;
          continue;
        }
        if (!empty($update['pending'])) {
          $updates_per_module += [$module => []];
          $updates_per_module[$module] = array_merge($updates_per_module[$module], $update['pending']);
          $build['start'][$module] = [
            '#type' => 'hidden',
            '#value' => $update['start'],
          ];
          // Store the previous items in order to merge normal updates and
          // post_update functions together.
          $build['start'][$module] = [
            '#theme' => 'item_list',
            '#items' => $updates_per_module[$module],
            '#title' => $module . ' module',
          ];

          if ($update_type === 'update') {
            $starting_updates[$module] = $update['start'];
          }
        }
        if (isset($update['pending'])) {
          $count = $count + count($update['pending']);
        }
      }
    }

    // Find and label any incompatible updates.
    foreach (update_resolve_dependencies($starting_updates) as $data) {
      if (!$data['allowed']) {
        $incompatible_updates_exist = TRUE;
        $incompatible_count++;
        $module_update_key = $data['module'] . '_updates';
        if (isset($build['start'][$module_update_key]['#items'][$data['number']])) {
          if ($data['missing_dependencies']) {
            $text = $this->t('This update will been skipped due to the following missing dependencies:') . '<em>' . implode(', ', $data['missing_dependencies']) . '</em>';
          }
          else {
            $text = $this->t("This update will be skipped due to an error in the module's code.");
          }
          $build['start'][$module_update_key]['#items'][$data['number']] .= '<div class="warning">' . $text . '</div>';
        }
        // Move the module containing this update to the top of the list.
        $build['start'] = [$module_update_key => $build['start'][$module_update_key]] + $build['start'];
      }
    }

    // Warn the user if any updates were incompatible.
    if ($incompatible_updates_exist) {
      $this->messenger()->addWarning($this->t('Some of the pending updates cannot be applied because their dependencies were not met.'));
    }

    if (empty($count)) {
      $this->messenger()->addStatus($this->t('No pending updates.'));
      unset($build);
      $build['links'] = [
        '#theme' => 'links',
        '#links' => $this->helpfulLinks($request),
      ];

      // No updates to run, so caches won't get flushed later.  Clear them now.
      drupal_flush_all_caches();
    }
    else {
      $build['help'] = [
        '#markup' => '<p>' . $this->t('The version of Drupal you are updating from has been automatically detected.') . '</p>',
        '#weight' => -5,
      ];
      if ($incompatible_count) {
        $build['start']['#title'] = $this->formatPlural(
          $count,
          '1 pending update (@number_applied to be applied, @number_incompatible skipped)',
          '@count pending updates (@number_applied to be applied, @number_incompatible skipped)',
          ['@number_applied' => $count - $incompatible_count, '@number_incompatible' => $incompatible_count]
        );
      }
      else {
        $build['start']['#title'] = $this->formatPlural($count, '1 pending update', '@count pending updates');
      }
      // @todo Simplify with https://www.drupal.org/node/2548095
      $base_url = str_replace('/update.php', '', $request->getBaseUrl());
      $url = (new Url('system.db_update', ['op' => 'run']))->setOption('base_url', $base_url);
      $build['link'] = [
        '#type' => 'link',
        '#title' => $this->t('Apply pending updates'),
        '#attributes' => ['class' => ['button', 'button--primary']],
        '#weight' => 5,
        '#url' => $url,
        '#access' => $url->access($this->currentUser()),
      ];
    }

    return $build;
  }

  /**
   * Displays results of the update script with any accompanying errors.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array.
   */
  protected function results(Request $request) {
    // @todo Simplify with https://www.drupal.org/node/2548095
    $base_url = str_replace('/update.php', '', $request->getBaseUrl());

    // Retrieve and remove session information.
    $session = $request->getSession();
    $update_results = $session->remove('update_results');
    $update_success = $session->remove('update_success');
    $session->remove('update_ignore_warnings');

    // Report end result.
    $dblog_exists = $this->moduleHandler->moduleExists('dblog');
    if ($dblog_exists && $this->account->hasPermission('access site reports')) {
      $log_message = $this->t('All errors have been <a href=":url">logged</a>.', [
        ':url' => Url::fromRoute('dblog.overview')->setOption('base_url', $base_url)->toString(TRUE)->getGeneratedUrl(),
      ]);
    }
    else {
      $log_message = $this->t('All errors have been logged.');
    }

    if ($update_success) {
      $message = '<p>' . $this->t('Updates were attempted. If you see no failures below, you may proceed happily back to your <a href=":url">site</a>. Otherwise, you may need to update your database manually.', [':url' => Url::fromRoute('<front>')->setOption('base_url', $base_url)->toString(TRUE)->getGeneratedUrl()]) . ' ' . $log_message . '</p>';
    }
    else {
      $last = $session->get('updates_remaining');
      $last = reset($last);
      [$module, $version] = array_pop($last);
      $message = '<p class="error">' . $this->t('The update process was aborted prematurely while running <strong>update #@version in @module.module</strong>.', [
        '@version' => $version,
        '@module' => $module,
      ]) . ' ' . $log_message;
      if ($dblog_exists) {
        $message .= ' ' . $this->t('You may need to check the <code>watchdog</code> database table manually.');
      }
      $message .= '</p>';
    }

    if (Settings::get('update_free_access')) {
      $message .= '<p>' . $this->t("<strong>Reminder: don't forget to set the <code>\$settings['update_free_access']</code> value in your <code>settings.php</code> file back to <code>FALSE</code>.</strong>") . '</p>';
    }

    $build['message'] = [
      '#markup' => $message,
    ];
    $build['links'] = [
      '#theme' => 'links',
      '#links' => $this->helpfulLinks($request),
    ];

    // Output a list of info messages.
    if (!empty($update_results)) {
      $all_messages = [];
      foreach ($update_results as $module => $updates) {
        if ($module != '#abort') {
          $module_has_message = FALSE;
          $info_messages = [];
          foreach ($updates as $name => $queries) {
            $messages = [];
            foreach ($queries as $query) {
              // If there is no message for this update, don't show anything.
              if (empty($query['query'])) {
                continue;
              }

              if ($query['success']) {
                $messages[] = [
                  '#wrapper_attributes' => ['class' => ['success']],
                  '#markup' => $query['query'],
                ];
              }
              else {
                $messages[] = [
                  '#wrapper_attributes' => ['class' => ['failure']],
                  '#markup' => '<strong>' . $this->t('Failed:') . '</strong> ' . $query['query'],
                ];
              }
            }

            if ($messages) {
              $module_has_message = TRUE;
              if (is_numeric($name)) {
                $title = $this->t('Update #@count', ['@count' => $name]);
              }
              else {
                $title = $this->t('Update @name', ['@name' => trim($name, '_')]);
              }
              $info_messages[] = [
                '#theme' => 'item_list',
                '#items' => $messages,
                '#title' => $title,
              ];
            }
          }

          // If there were any messages then prefix them with the module name
          // and add it to the global message list.
          if ($module_has_message) {
            $all_messages[] = [
              '#type' => 'container',
              '#prefix' => '<h3>' . $this->t('@module module', ['@module' => $module]) . '</h3>',
              '#children' => $info_messages,
            ];
          }
        }
      }
      if ($all_messages) {
        $build['query_messages'] = [
          '#type' => 'container',
          '#children' => $all_messages,
          '#attributes' => ['class' => ['update-results']],
          '#prefix' => '<h2>' . $this->t('The following updates returned messages:') . '</h2>',
        ];
      }
    }

    return $build;
  }

  /**
   * Renders a list of requirement errors or warnings.
   *
   * @param $severity
   *   The severity of the message, as per RFC 3164.
   * @param array $requirements
   *   The array of requirement values.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array.
   */
  public function requirements($severity, array $requirements, Request $request) {
    $options = $severity == REQUIREMENT_WARNING ? ['continue' => 1] : [];
    // @todo Revisit once https://www.drupal.org/node/2548095 is in. Something
    // like Url::fromRoute('system.db_update')->setOptions() should then be
    // possible.
    $try_again_url = Url::fromUri($request->getUriForPath(''))->setOptions(['query' => $options])->toString(TRUE)->getGeneratedUrl();

    $build['status_report'] = [
      '#type' => 'status_report',
      '#requirements' => $requirements,
      '#suffix' => $this->t('Check the messages and <a href=":url">try again</a>.', [':url' => $try_again_url]),
    ];

    $build['#title'] = $this->t('Requirements problem');
    return $build;
  }

  /**
   * Provides the update task list render array.
   *
   * @param string $active
   *   The active task.
   *   Can be one of 'requirements', 'info', 'selection', 'run', 'results'.
   *
   * @return array
   *   A render array.
   */
  protected function updateTasksList($active = NULL) {
    // Default list of tasks.
    $tasks = [
      'requirements' => $this->t('Verify requirements'),
      'info' => $this->t('Overview'),
      'selection' => $this->t('Review updates'),
      'run' => $this->t('Run updates'),
      'results' => $this->t('Review log'),
    ];

    $task_list = [
      '#theme' => 'maintenance_task_list',
      '#items' => $tasks,
      '#active' => $active,
    ];
    return $task_list;
  }

  /**
   * Starts the database update batch process.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   */
  protected function triggerBatch(Request $request) {
    $maintenance_mode = $this->state->get('system.maintenance_mode', FALSE);
    // Store the current maintenance mode status in the session so that it can
    // be restored at the end of the batch.
    $request->getSession()->set('maintenance_mode', $maintenance_mode);
    // During the update, always put the site into maintenance mode so that
    // in-progress schema changes do not affect visiting users.
    if (empty($maintenance_mode)) {
      $this->state->set('system.maintenance_mode', TRUE);
    }

    /** @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Updating'))
      ->setInitMessage($this->t('Starting updates'))
      ->setErrorMessage($this->t('An unrecoverable error has occurred. You can find the error message below. It is advised to copy it to the clipboard for reference.'))
      ->setFinishCallback([DbUpdateController::class, 'batchFinished']);

    // Resolve any update dependencies to determine the actual updates that will
    // be run and the order they will be run in.
    $start = $this->getModuleUpdates();
    $updates = update_resolve_dependencies($start);

    // Store the dependencies for each update function in an array which the
    // batch API can pass in to the batch operation each time it is called. (We
    // do not store the entire update dependency array here because it is
    // potentially very large.)
    $dependency_map = [];
    foreach ($updates as $function => $update) {
      $dependency_map[$function] = !empty($update['reverse_paths']) ? array_keys($update['reverse_paths']) : [];
    }

    // Determine updates to be performed.
    foreach ($updates as $function => $update) {
      if ($update['allowed']) {
        // Set the installed version of each module so updates will start at the
        // correct place. (The updates are already sorted, so we can simply base
        // this on the first one we come across in the above foreach loop.)
        if (isset($start[$update['module']])) {
          \Drupal::service('update.update_hook_registry')->setInstalledVersion($update['module'], $update['number'] - 1);
          unset($start[$update['module']]);
        }
        $batch_builder->addOperation('update_do_one', [$update['module'], $update['number'], $dependency_map[$function]]);
      }
    }

    $post_updates = $this->postUpdateRegistry->getPendingUpdateFunctions();

    if ($post_updates) {
      // Now we rebuild all caches and after that execute the hook_post_update()
      // functions.
      $batch_builder->addOperation('drupal_flush_all_caches', []);
      foreach ($post_updates as $function) {
        $batch_builder->addOperation('update_invoke_post_update', [$function]);
      }
    }

    batch_set($batch_builder->toArray());

    // @todo Revisit once https://www.drupal.org/node/2548095 is in.
    return batch_process(Url::fromUri('base://results'), Url::fromUri('base://start'));
  }

  /**
   * Finishes the update process and stores the results for eventual display.
   *
   * After the updates run, all caches are flushed. The update results are
   * stored into the session (for example, to be displayed on the update results
   * page in update.php). Additionally, if the site was off-line, now that the
   * update process is completed, the site is set back online.
   *
   * @param $success
   *   Indicate that the batch API tasks were all completed successfully.
   * @param array $results
   *   An array of all the results that were updated in update_do_one().
   * @param array $operations
   *   A list of all the operations that had not been completed by the batch API.
   */
  public static function batchFinished($success, $results, $operations) {
    // No updates to run, so caches won't get flushed later.  Clear them now.
    drupal_flush_all_caches();

    $session = \Drupal::request()->getSession();
    $session->set('update_results', $results);
    $session->set('update_success', $success);
    $session->set('updates_remaining', $operations);

    // Now that the update is done, we can put the site back online if it was
    // previously not in maintenance mode.
    if (!$session->remove('maintenance_mode')) {
      \Drupal::state()->set('system.maintenance_mode', FALSE);
    }
  }

  /**
   * Provides links to the homepage and administration pages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   An array of links.
   */
  protected function helpfulLinks(Request $request) {
    // @todo Simplify with https://www.drupal.org/node/2548095
    $base_url = str_replace('/update.php', '', $request->getBaseUrl());
    $links['front'] = [
      'title' => $this->t('Front page'),
      'url' => Url::fromRoute('<front>')->setOption('base_url', $base_url),
    ];
    if ($this->account->hasPermission('access administration pages')) {
      $links['admin-pages'] = [
        'title' => $this->t('Administration pages'),
        'url' => Url::fromRoute('system.admin')->setOption('base_url', $base_url),
      ];
    }
    return $links;
  }

  /**
   * Retrieves module updates.
   *
   * @return array
   *   The module updates that can be performed.
   */
  protected function getModuleUpdates() {
    $return = [];
    $updates = update_get_update_list();
    foreach ($updates as $module => $update) {
      $return[$module] = $update['start'];
    }

    return $return;
  }

}
