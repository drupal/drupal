<?php

/**
 * @file
 * Contains \Drupal\config\Form\ConfigSync.
 */

namespace Drupal\config\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct the storage changes in a configuration synchronization form.
 */
class ConfigSync extends FormBase {

  /**
   * The database lock object.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The staging configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $stagingStorage;

  /**
   * The active configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The snapshot configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $snapshotStorage;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface;
   */
  protected $configManager;

  /**
   * URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Config\StorageInterface $staging_storage
   *   The source storage.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $snapshot_storage
   *   The snapshot storage.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock object.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Configuration manager.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler
   */
  public function __construct(StorageInterface $staging_storage, StorageInterface $active_storage, StorageInterface $snapshot_storage, LockBackendInterface $lock, EventDispatcherInterface $event_dispatcher, ConfigManagerInterface $config_manager, UrlGeneratorInterface $url_generator, TypedConfigManagerInterface $typed_config, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->stagingStorage = $staging_storage;
    $this->activeStorage = $active_storage;
    $this->snapshotStorage = $snapshot_storage;
    $this->lock = $lock;
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->urlGenerator = $url_generator;
    $this->typedConfigManager = $typed_config;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.staging'),
      $container->get('config.storage'),
      $container->get('config.storage.snapshot'),
      $container->get('lock'),
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('url_generator'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_admin_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $snapshot_comparer = new StorageComparer($this->activeStorage, $this->snapshotStorage, $this->configManager);
    if (!$form_state->getUserInput() && $snapshot_comparer->createChangelist()->hasChanges()) {
      $change_list = array();
      foreach ($snapshot_comparer->getAllCollectionNames() as $collection) {
        foreach ($snapshot_comparer->getChangelist(NULL, $collection) as $config_names) {
          if (empty($config_names)) {
            continue;
          }
          foreach ($config_names as $config_name) {
            $change_list[] = $config_name;
          }
        }
      }
      sort($change_list);
      $change_list_render = array(
        '#theme' => 'item_list',
        '#items' => $change_list,
      );
      $change_list_html = drupal_render($change_list_render);
      drupal_set_message($this->t('Your current configuration has changed. Changes to these configuration items will be lost on the next synchronization: !changes', array('!changes' => $change_list_html)), 'warning');
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import all'),
    );

    $source_list = $this->stagingStorage->listAll();
    $storage_comparer = new StorageComparer($this->stagingStorage, $this->activeStorage, $this->configManager);
    if (empty($source_list) || !$storage_comparer->createChangelist()->hasChanges()) {
      $form['no_changes'] = array(
        '#type' => 'table',
        '#header' => array('Name', 'Operations'),
        '#rows' => array(),
        '#empty' => $this->t('There are no configuration changes to import.'),
      );
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    elseif (!$storage_comparer->validateSiteUuid()) {
      drupal_set_message($this->t('The staged configuration cannot be imported, because it originates from a different site than this site. You can only synchronize configuration between cloned instances of this site.'), 'error');
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    else {
      // Store the comparer for use in the submit.
      $form_state->set('storage_comparer', $storage_comparer);
    }

    // Add the AJAX library to the form for dialog support.
    $form['#attached']['library'][] = 'core/drupal.ajax';

    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      if ($collection != StorageInterface::DEFAULT_COLLECTION) {
        $form[$collection]['collection_heading'] = array(
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('!collection configuration collection', array('!collection' => $collection)),
        );
      }
      foreach ($storage_comparer->getChangelist(NULL, $collection) as $config_change_type => $config_names) {
        if (empty($config_names)) {
          continue;
        }

        // @todo A table caption would be more appropriate, but does not have the
        //   visual importance of a heading.
        $form[$collection][$config_change_type]['heading'] = array(
          '#type' => 'html_tag',
          '#tag' => 'h3',
        );
        switch ($config_change_type) {
          case 'create':
            $form[$collection][$config_change_type]['heading']['#value'] = format_plural(count($config_names), '@count new', '@count new');
            break;

          case 'update':
            $form[$collection][$config_change_type]['heading']['#value'] = format_plural(count($config_names), '@count changed', '@count changed');
            break;

          case 'delete':
            $form[$collection][$config_change_type]['heading']['#value'] = format_plural(count($config_names), '@count removed', '@count removed');
            break;

          case 'rename':
            $form[$collection][$config_change_type]['heading']['#value'] = format_plural(count($config_names), '@count renamed', '@count renamed');
            break;
        }
        $form[$collection][$config_change_type]['list'] = array(
          '#type' => 'table',
          '#header' => array('Name', 'Operations'),
        );

        foreach ($config_names as $config_name) {
          if ($config_change_type == 'rename') {
            $names = $storage_comparer->extractRenameNames($config_name);
            $route_options = array('source_name' => $names['old_name'], 'target_name' => $names['new_name']);
            $config_name = $this->t('!source_name to !target_name', array('!source_name' => $names['old_name'], '!target_name' => $names['new_name']));
          }
          else {
            $route_options = array('source_name' => $config_name);
          }
          if ($collection != StorageInterface::DEFAULT_COLLECTION) {
            $route_options['collection'] = $collection;
            $href = $this->urlGenerator->getPathFromRoute('config.diff_collection', $route_options);
          }
          else {
            $href = $this->urlGenerator->getPathFromRoute('config.diff', $route_options);
          }
          $links['view_diff'] = array(
            'title' => $this->t('View differences'),
            'href' => $href,
            'attributes' => array(
              'class' => array('use-ajax'),
              'data-accepts' => 'application/vnd.drupal-modal',
              'data-dialog-options' => json_encode(array(
                'width' => 700
              )),
            ),
          );
          $form[$collection][$config_change_type]['list']['#rows'][] = array(
            'name' => $config_name,
            'operations' => array(
              'data' => array(
                '#type' => 'operations',
                '#links' => $links,
              ),
            ),
          );
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_importer = new ConfigImporter(
      $form_state->get('storage_comparer'),
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->typedConfigManager,
      $this->moduleHandler,
      $this->themeHandler,
      $this->getStringTranslation()
    );
    if ($config_importer->alreadyImporting()) {
      drupal_set_message($this->t('Another request may be synchronizing configuration already.'));
    }
    else{
      try {
        $sync_steps = $config_importer->initialize();
        $batch = array(
          'operations' => array(),
          'finished' => array(get_class($this), 'finishBatch'),
          'title' => t('Synchronizing configuration'),
          'init_message' => t('Starting configuration synchronization.'),
          'progress_message' => t('Completed @current step of @total.'),
          'error_message' => t('Configuration synchronization has encountered an error.'),
          'file' => drupal_get_path('module', 'config') . '/config.admin.inc',
        );
        foreach ($sync_steps as $sync_step) {
          $batch['operations'][] = array(array(get_class($this), 'processBatch'), array($config_importer, $sync_step));
        }

        batch_set($batch);
      }
      catch (ConfigImporterException $e) {
        // There are validation errors.
        drupal_set_message($this->t('The configuration synchronization failed validation.'));
        foreach ($config_importer->getErrors() as $message) {
          drupal_set_message($message, 'error');
        }
      }
    }
  }

  /**
   * Processes the config import batch and persists the importer.
   *
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The batch config importer object to persist.
   * @param string $sync_step
   *   The synchronisation step to do.
   * @param $context
   *   The batch context.
   */
  public static function processBatch(ConfigImporter $config_importer, $sync_step, &$context) {
    if (!isset($context['sandbox']['config_importer'])) {
      $context['sandbox']['config_importer'] = $config_importer;
    }

    $config_importer = $context['sandbox']['config_importer'];
    $config_importer->doSyncStep($sync_step, $context);
    if ($errors = $config_importer->getErrors()) {
      if (!isset($context['results']['errors'])) {
        $context['results']['errors'] = array();
      }
      $context['results']['errors'] += $errors;
    }
  }

  /**
   * Finish batch.
   *
   * This function is a static function to avoid serialising the ConfigSync
   * object unnecessarily.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          drupal_set_message($error, 'error');
          \Drupal::logger('config_sync')->error($error);
        }
        drupal_set_message(\Drupal::translation()->translate('The configuration was imported with errors.'), 'warning');
      }
      else {
        drupal_set_message(\Drupal::translation()->translate('The configuration was imported successfully.'));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = \Drupal::translation()->translate('An error occurred while processing %error_operation with arguments: @arguments', array('%error_operation' => $error_operation[0], '@arguments' => print_r($error_operation[1], TRUE)));
      drupal_set_message($message, 'error');
    }
    drupal_flush_all_caches();
  }


}
