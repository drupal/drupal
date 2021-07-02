<?php

namespace Drupal\Core\Batch;

use Drupal\Component\Utility\Timer;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormSubmitterInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Queue\Batch;
use Drupal\Core\Queue\BatchMemory;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Implements the default batch processor.
 */
class BatchProcessor implements BatchProcessorInterface {

  use StringTranslationTrait;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The batch storage service.
   *
   * @var \Drupal\Core\Batch\BatchStorageInterface
   */
  protected $batchStorage;

  /**
   * The date formatter used to calculate the needed time for the batch.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The form submitter used to redirect at the end of the batch.
   *
   * @var \Drupal\Core\Form\FormSubmitterInterface
   */
  protected $formSubmitter;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * In memory batch  cache.
   *
   * @var array
   */
  protected $batch;

  /**
   * Creates a new BatchProcessor.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Batch\BatchStorageInterface $batch_storage
   *   The batch storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter used to calculate the needed time for the batch.
   * @param \Drupal\Core\Form\FormSubmitterInterface $form_submitter
   *   The form submitter used to redirect at the end of the batch.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   * @param \Drupal\Core\Database\Connection $database
   *   The connection to the database.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   */
  public function __construct($root, BatchStorageInterface $batch_storage, DateFormatterInterface $date_formatter, FormSubmitterInterface $form_submitter, RequestStack $request_stack, PathValidatorInterface $path_validator, Connection $database, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager, RouteMatchInterface $route_match) {
    $this->root = $root;
    $this->batchStorage = $batch_storage;
    $this->dateFormatter = $date_formatter;
    $this->formSubmitter = $form_submitter;
    $this->requestStack = $request_stack;
    $this->pathValidator = $path_validator;
    $this->connection = $database;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->routeMatch = $route_match;
    $this->batch = [];
  }

  /**
   * {@inheritdoc}
   */
  public function queue($batch_definition) {
    if ($batch_definition) {
      $batch = &$this->getCurrentBatch();

      // Initialize the batch if needed.
      if (empty($batch)) {
        $batch = [
          'sets' => [],
          'has_form_submits' => FALSE,
        ];
      }

      // Base and default properties for the batch set.
      $init = [
        'sandbox' => [],
        'results' => [],
        'success' => FALSE,
        'start' => 0,
        'elapsed' => 0,
      ];
      $defaults = [
        'title' => t('Processing'),
        'init_message' => t('Initializing.'),
        'progress_message' => t('Completed @current of @total.'),
        'error_message' => t('An error has occurred.'),
      ];
      $batch_set = $init + $batch_definition + $defaults;

      // Tweak init_message to avoid the bottom of the page flickering down
      // after init phase.
      $batch_set['init_message'] .= '<br/>&nbsp;';

      // The non-concurrent workflow of batch execution allows us to save
      // numberOfItems() queries by handling our own counter.
      $batch_set['total'] = count($batch_set['operations']);
      $batch_set['count'] = $batch_set['total'];

      // Add the set to the batch.
      if (empty($batch['id'])) {
        // The batch is not running yet. Simply add the new set.
        $batch['sets'][] = $batch_set;
      }
      else {
        // The set is being added while the batch is running. Insert the new set
        // right after the current one to ensure execution order, and store its
        // operations in a queue.
        $index = $batch['current_set'] + 1;
        $slice1 = array_slice($batch['sets'], 0, $index);
        $slice2 = array_slice($batch['sets'], $index);
        $batch['sets'] = array_merge($slice1, [$batch_set], $slice2);
        $this->queuePopulate($batch, $index);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function queuePopulate(array &$batch, $set_id) {
    $batch_set = &$batch['sets'][$set_id];

    if (isset($batch_set['operations'])) {
      $batch_set += [
        'queue' => [
          'name' => 'drupal_batch:' . $batch['id'] . ':' . $set_id,
          'class' => $batch['progressive'] ? Batch::class : BatchMemory::class,
        ],
      ];

      $queue = $this->getQueue($batch_set);
      $queue->createQueue();
      foreach ($batch_set['operations'] as $operation) {
        $queue->createItem($operation);
      }

      unset($batch_set['operations']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQueue(array $batch_set) {
    static $queues;

    if (!isset($queues)) {
      $queues = [];
    }

    if (isset($batch_set['queue'])) {
      $name = $batch_set['queue']['name'];
      $class = $batch_set['queue']['class'];

      if (!isset($queues[$class][$name])) {
        $queues[$class][$name] = new $class($name, $this->connection);
      }
      return $queues[$class][$name];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($redirect = NULL, Url $url = NULL, $redirect_callback = NULL) {
    $batch = &$this->getCurrentBatch();

    if (isset($batch)) {
      // Add process information.
      $process_info = [
        'current_set' => 0,
        'progressive' => TRUE,
        'url' => isset($url) ? $url : Url::fromRoute('system.batch_page.html'),
        'source_url' => Url::fromRouteMatch($this->routeMatch)->mergeOptions(['query' => $this->requestStack->getCurrentRequest()->query->all()]),
        'batch_redirect' => $redirect,
        'theme' => $this->themeManager->getActiveTheme()->getName(),
        'redirect_callback' => $redirect_callback,
      ];
      $batch += $process_info;

      // The batch is now completely built. Allow other modules to make changes
      // to the batch so that it is easier to reuse batch processes in other
      // environments.
      $this->moduleHandler->alter('batch', $batch);

      // Assign an arbitrary id: don't rely on a serial column in the 'batch'
      // table, since non-progressive batches skip database storage completely.
      $batch['id'] = $this->connection->nextId();;

      // Move operations to a job queue. Non-progressive batches will use a
      // memory-based queue.
      foreach ($batch['sets'] as $key => $batch_set) {
        $this->queuePopulate($batch, $key);
      }

      // Initiate processing.
      if ($batch['progressive']) {
        // Now that we have a batch id, we can generate the redirection link in
        // the generic error message.
        /** @var \Drupal\Core\Url $batch_url */
        $batch_url = $batch['url'];
        /** @var \Drupal\Core\Url $error_url */
        $error_url = clone $batch_url;
        $query_options = $error_url->getOption('query');
        $query_options['id'] = $batch['id'];
        $query_options['op'] = 'finished';
        $error_url->setOption('query', $query_options);

        $batch['error_message'] = $this->t('Please continue to <a href=":error_url">the error page</a>', [':error_url' => $error_url->toString(TRUE)->getGeneratedUrl()]);

        // Clear the way for the redirection to the batch processing page, by
        // saving and unsetting the 'destination', if there is any.
        $request = $this->requestStack->getCurrentRequest();
        if ($request->query->has('destination')) {
          $batch['destination'] = $request->query->get('destination');
          $request->query->remove('destination');
        }

        // Store the batch.
        $this->batchStorage->create($batch);

        // Set the batch number in the session to guarantee that it will stay
        // alive.
        $_SESSION['batches'][$batch['id']] = TRUE;

        // Redirect for processing.
        $query_options = $error_url->getOption('query');
        $query_options['op'] = 'start';
        $query_options['id'] = $batch['id'];
        $batch_url->setOption('query', $query_options);
        if (($function = $batch['redirect_callback']) && function_exists($function)) {
          $function($batch_url->toString(), ['query' => $query_options]);
        }
        else {
          return new RedirectResponse($batch_url->setAbsolute()->toString(TRUE)->getGeneratedUrl());
        }
      }
      else {
        // Non-progressive execution: bypass the whole progressbar workflow
        // and execute the batch in one pass.
        $this->processQueue();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function &getCurrentBatch() {
    return $this->batch;
  }

  /**
   * {@inheritdoc}
   */
  public function processQueue() {
    $batch       = &$this->getCurrentBatch();
    $current_set = &$this->getCurrentSet();
    // Indicate that this batch set needs to be initialized.
    $set_changed = TRUE;

    // If this batch was marked for progressive execution (e.g. forms submitted
    // by \Drupal::formBuilder()->submitForm(), initialize a timer to determine
    // whether we need to proceed with the same batch phase when a processing
    // time of 1 second has been exceeded.
    if ($batch['progressive']) {
      Timer::start('batch_processing');
    }

    if (empty($current_set['start'])) {
      $current_set['start'] = microtime(TRUE);
    }

    $queue = $this->getQueue($current_set);
    // Set the completion level to 1 by default.
    $finished = 1;
    // Initialize $old_set.
    $old_set = $current_set;
    while (!$current_set['success']) {
      // If this is the first time we iterate this batch set in the current
      // request, we check if it requires an additional file for functions
      // definitions.
      if ($set_changed && isset($current_set['file']) && is_file($current_set['file'])) {
        include_once $this->root . '/' . $current_set['file'];
      }

      $task_message = $label = '';
      // Assume a single pass operation and set the completion level to 1 by
      // default.
      $finished = 1;

      if ($item = $queue->claimItem()) {
        list($callback, $args) = $item->data;

        // Build the 'context' array and execute the function call.
        $batch_context = [
          'sandbox'  => &$current_set['sandbox'],
          'results'  => &$current_set['results'],
          'finished' => &$finished,
          'message'  => &$task_message,
        ];
        call_user_func_array($callback, array_merge($args, [&$batch_context]));

        if ($finished >= 1) {
          // Make sure this step is not counted twice when computing $current.
          $finished = 0;
          // Remove the processed operation and clear the sandbox.
          $queue->deleteItem($item);
          $current_set['count']--;
          $current_set['sandbox'] = [];
        }
      }

      // When all operations in the current batch set are completed, browse
      // through the remaining sets, marking them 'successfully processed'
      // along the way, until we find a set that contains operations.
      // _batch_next_set() executes form submit handlers stored in 'control'
      // sets (see \Drupal::service('form_submitter')), which can in turn add
      // new sets to the batch.
      $set_changed = FALSE;
      $old_set = $current_set;
      while (empty($current_set['count']) && ($current_set['success'] = TRUE) && $this->nextSet()) {
        $current_set = &$this->getCurrentSet();
        $current_set['start'] = microtime(TRUE);
        $set_changed = TRUE;
      }

      // At this point, either $current_set contains operations that need to be
      // processed or all sets have been completed.
      $queue = $this->getQueue($current_set);

      // If we are in progressive mode, break processing after 1 second.
      if ($batch['progressive'] && Timer::read('batch_processing') > 1000) {
        // Record elapsed wall clock time.
        $current_set['elapsed'] = round((microtime(TRUE) - $current_set['start']) * 1000, 2);
        break;
      }
    }

    if ($batch['progressive']) {
      // Gather progress information.
      // Reporting 100% progress will cause the whole batch to be considered
      // processed. If processing was paused right after moving to a new set,
      // we have to use the info from the new (unprocessed) set.
      if ($set_changed && isset($current_set['queue'])) {
        // Processing will continue with a fresh batch set.
        $remaining        = $current_set['count'];
        $total            = $current_set['total'];
        $progress_message = $current_set['init_message'];
        $task_message     = '';
      }
      else {
        // Processing will continue with the current batch set.
        $remaining        = $old_set['count'];
        $total            = $old_set['total'];
        $progress_message = $old_set['progress_message'];
      }

      // Total progress is the number of operations that have fully run plus the
      // completion level of the current operation.
      $current    = $total - $remaining + $finished;
      $percentage = _batch_api_percentage($total, $current);
      $elapsed    = isset($current_set['elapsed']) ? $current_set['elapsed'] : 0;
      $values     = [
        '@remaining'  => $remaining,
        '@total'      => $total,
        '@current'    => floor($current),
        '@percentage' => $percentage,
        '@elapsed'    => $this->dateFormatter->formatInterval($elapsed / 1000),
        // If possible, estimate remaining processing time.
        '@estimate'   => ($current > 0) ? $this->dateFormatter->formatInterval(($elapsed * ($total - $current) / $current) / 1000) : '-',
      ];
      $message = strtr($progress_message, $values);
      if (!empty($task_message)) {
        $label = $task_message;
      }

      return [$percentage, $message, $label];
    }
    else {
      // If we are not in progressive mode, the entire batch has been processed.
      return $this->finishedProcessing();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function &getCurrentSet() {
    $batch = &$this->getCurrentBatch();
    return $batch['sets'][$batch['current_set']];
  }

  /**
   * {@inheritdoc}
   */
  public function nextSet() {
    $batch = &$this->getCurrentBatch();
    if (isset($batch['sets'][$batch['current_set'] + 1])) {
      $batch['current_set']++;
      $current_set = &$this->getCurrentSet();
      if (isset($current_set['form_submit']) && ($callback = $current_set['form_submit']) && is_callable($callback)) {
        // We use our stored copies of $form and $form_state to account for
        // possible alterations by previous form submit handlers.
        $complete_form = &$batch['form_state']->getCompleteForm();
        call_user_func_array($callback, [&$complete_form, &$batch['form_state']]);
      }
      return TRUE;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function finishedProcessing() {
    $batch = &$this->getCurrentBatch();
    $batch_finished_redirect = NULL;

    // Execute the 'finished' callbacks for each batch set, if defined.
    foreach ($batch['sets'] as $batch_set) {
      if (isset($batch_set['finished'])) {
        // Check if the set requires an additional file for function
        // definitions.
        if (isset($batch_set['file']) && is_file($batch_set['file'])) {
          include_once $this->root . '/' . $batch_set['file'];
        }
        if (is_callable($batch_set['finished'])) {
          $queue = $this->getQueue($batch_set);
          $operations = $queue->getAllItems();
          $batch_set_result = call_user_func_array($batch_set['finished'], [
            $batch_set['success'],
            $batch_set['results'],
            $operations,
            $this->dateFormatter
              ->formatInterval($batch_set['elapsed'] / 1000),
          ]);
          // If a batch 'finished' callback requested a redirect after the batch
          // is complete, save that for later use. If more than one batch set
          // returned a redirect, the last one is used.
          if ($batch_set_result instanceof RedirectResponse) {
            $batch_finished_redirect = $batch_set_result;
          }
        }
      }
    }

    // Clean up the batch table and unset the static $batch variable.
    if ($batch['progressive']) {
      $this->batchStorage->delete($batch['id']);
      foreach ($batch['sets'] as $batch_set) {
        if ($queue = $this->getQueue($batch_set)) {
          $queue->deleteQueue();
        }
      }
      // Clean-up the session. Not needed for CLI updates.
      if (isset($_SESSION)) {
        unset($_SESSION['batches'][$batch['id']]);
        if (empty($_SESSION['batches'])) {
          unset($_SESSION['batches']);
        }
      }
    }
    $_batch = $batch;
    $batch = NULL;

    // Redirect if needed.
    if ($_batch['progressive']) {
      // Revert the 'destination' that was saved in batch_process().
      if (isset($_batch['destination'])) {
        $this->requestStack->getCurrentRequest()->query->set('destination', $_batch['destination']);
      }

      // Determine the target path to redirect to. If a batch 'finished'
      // callback returned a redirect response object, use that. Otherwise, fall
      // back on the form redirection.
      if (isset($batch_finished_redirect)) {
        return $batch_finished_redirect;
      }
      elseif (!isset($_batch['form_state'])) {
        $_batch['form_state'] = new FormState();
      }
      if ($_batch['form_state']->getRedirect() === NULL) {
        $redirect = $_batch['batch_redirect'] ?: $_batch['source_url'];
        // Any path with a scheme does not correspond to a route.
        if (!$redirect instanceof Url) {
          $options = UrlHelper::parse($redirect);
          if (parse_url($options['path'], PHP_URL_SCHEME)) {
            $redirect = Url::fromUri($options['path'], $options);
          }
          else {
            $redirect = $this->pathValidator->getUrlIfValid($options['path']);
            if (!$redirect) {
              // Stay on the same page if the redirect was invalid.
              $redirect = Url::fromRoute('<current>');
            }
            $redirect->setOptions($options);
          }
        }
        $_batch['form_state']->setRedirectUrl($redirect);
      }

      // Use \Drupal\Core\Form\FormSubmitterInterface::redirectForm() to handle
      // the redirection logic.
      $redirect = $this->formSubmitter->redirectForm($_batch['form_state']);
      if (is_object($redirect)) {
        return $redirect;
      }

      // If no redirection happened, redirect to the originating page. In case
      // the form needs to be rebuilt, save the final $form_state for
      // \Drupal\Core\Form\FormBuilderInterface::buildForm().
      if ($_batch['form_state']->isRebuilding()) {
        $_SESSION['batch_form_state'] = $_batch['form_state'];
      }
      $callback = $_batch['redirect_callback'];
      $_batch['source_url']->mergeOptions(['query' => ['op' => 'finish', 'id' => $_batch['id']]]);
      if (is_callable($callback)) {
        $callback($_batch['source_url'], $_batch['source_url']->getOption('query'));
      }
      elseif ($callback === NULL) {
        // Default to RedirectResponse objects when nothing specified.
        return new RedirectResponse($_batch['source_url']->setAbsolute()->toString());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueForBatch($batch) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function shutdown() {
    if (($batch = $this->getCurrentBatch()) && _batch_needs_update()) {
      $this->batchStorage->update($batch);
    }
  }

}
