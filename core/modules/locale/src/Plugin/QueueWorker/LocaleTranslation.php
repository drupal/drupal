<?php

namespace Drupal\locale\Plugin\QueueWorker;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes interface translation queue tasks.
 *
 * @QueueWorker(
 *   id = "locale_translation",
 *   title = @Translation("Update translations"),
 *   cron = {"time" = 30}
 * )
 */
class LocaleTranslation extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Constructs a new LocaleTranslation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, QueueInterface $queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('queue')->get('locale_translation', TRUE)
    );
  }

  /**
   * {@inheritdoc}
   *
   * The translation update functions executed here are batch operations which
   * are also used in translation update batches. The batch functions may need
   * to be executed multiple times to complete their task, typically this is the
   * translation import function. When a batch function is not finished, a new
   * queue task is created and added to the end of the queue. The batch context
   * data is needed to continue the batch task is stored in the queue with the
   * queue data.
   */
  public function processItem($data) {
    $this->moduleHandler->loadInclude('locale', 'batch.inc');
    list($function, $args) = $data;

    // We execute batch operation functions here to check, download and import
    // the translation files. Batch functions use a context variable as last
    // argument which is passed by reference. When a batch operation is called
    // for the first time a default batch context is created. When called
    // iterative (usually the batch import function) the batch context is passed
    // through via the queue and is part of the $data.
    $last = count($args) - 1;
    if (!is_array($args[$last]) || !isset($args[$last]['finished'])) {
      $batch_context = [
        'sandbox'  => [],
        'results'  => [],
        'finished' => 1,
        'message'  => '',
      ];
    }
    else {
      $batch_context = $args[$last];
      unset ($args[$last]);
    }
    $args = array_merge($args, [&$batch_context]);

    // Call the batch operation function.
    call_user_func_array($function, $args);

    // If the batch operation is not finished we create a new queue task to
    // continue the task. This is typically the translation import task.
    if ($batch_context['finished'] < 1) {
      unset($batch_context['strings']);
      $this->queue->createItem([$function, $args]);
    }
  }

}
