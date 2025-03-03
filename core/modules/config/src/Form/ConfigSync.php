<?php

namespace Drupal\config\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\Importer\ConfigImporterBatch;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct the storage changes in a configuration synchronization form.
 *
 * @internal
 */
class ConfigSync extends FormBase {

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Config\StorageInterface $syncStorage
   *   The source storage.
   * @param \Drupal\Core\Config\StorageInterface $activeStorage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $snapshotStorage
   *   The snapshot storage.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock object.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   Event dispatcher.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   Configuration manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   * @param \Drupal\Core\Config\ImportStorageTransformer $importTransformer
   *   The import transformer service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list.
   */
  public function __construct(
    protected StorageInterface $syncStorage,
    protected StorageInterface $activeStorage,
    protected StorageInterface $snapshotStorage,
    protected LockBackendInterface $lock,
    protected EventDispatcherInterface $eventDispatcher,
    protected ConfigManagerInterface $configManager,
    protected TypedConfigManagerInterface $typedConfigManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected ModuleInstallerInterface $moduleInstaller,
    protected ThemeHandlerInterface $themeHandler,
    protected RendererInterface $renderer,
    protected ModuleExtensionList $moduleExtensionList,
    protected ImportStorageTransformer $importTransformer,
    protected ThemeExtensionList $themeExtensionList,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('config.storage.snapshot'),
      $container->get('lock.persistent'),
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler'),
      $container->get('renderer'),
      $container->get('extension.list.module'),
      $container->get('config.import_transformer'),
      $container->get('extension.list.theme')
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
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import all'),
    ];
    $syncStorage = $this->importTransformer->transform($this->syncStorage);
    $source_list = $syncStorage->listAll();
    $storage_comparer = new StorageComparer($syncStorage, $this->activeStorage);
    $storage_comparer->createChangelist();
    if (empty($source_list) || !$storage_comparer->hasChanges()) {
      $form['no_changes'] = [
        '#type' => 'table',
        '#header' => [$this->t('Name'), $this->t('Operations')],
        '#rows' => [],
        '#empty' => empty($source_list) ? $this->t('There is no staged configuration.') : $this->t('The staged configuration is identical to the active configuration.'),
      ];
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    elseif (!$storage_comparer->validateSiteUuid()) {
      $this->messenger()->addError($this->t('The staged configuration cannot be imported, because it originates from a different site than this site. You can only synchronize configuration between cloned instances of this site.'));
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    // A list of changes will be displayed, so check if the user should be
    // warned of potential losses to configuration.
    if ($this->snapshotStorage->exists('core.extension')) {
      $snapshot_comparer = new StorageComparer($this->activeStorage, $this->snapshotStorage);
      $snapshot_comparer->createChangelist();
      if (!$form_state->getUserInput() && $snapshot_comparer->hasChanges()) {
        $change_list = [];
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
        $message = [
          [
            '#markup' => $this->t('The following items in your active configuration have changes since the last import that may be lost on the next import.'),
          ],
          [
            '#theme' => 'item_list',
            '#items' => $change_list,
          ],
        ];
        $this->messenger()->addWarning($this->renderer->renderInIsolation($message));
      }
    }

    // Store the comparer for use in the submit.
    $form_state->set('storage_comparer', $storage_comparer);

    // Add the AJAX library to the form for dialog support.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      if ($collection != StorageInterface::DEFAULT_COLLECTION) {
        $form[$collection]['collection_heading'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('@collection configuration collection', ['@collection' => $collection]),
        ];
      }
      foreach ($storage_comparer->getChangelist(NULL, $collection) as $config_change_type => $config_names) {
        if (empty($config_names)) {
          continue;
        }

        // @todo A table caption would be more appropriate, but does not have the
        //   visual importance of a heading.
        $form[$collection][$config_change_type]['heading'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
        ];
        switch ($config_change_type) {
          case 'create':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count new', '@count new');
            break;

          case 'update':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count changed', '@count changed');
            break;

          case 'delete':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count removed', '@count removed');
            break;

          case 'rename':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count renamed', '@count renamed');
            break;
        }
        $form[$collection][$config_change_type]['list'] = [
          '#type' => 'table',
          '#header' => [$this->t('Name'), $this->t('Operations')],
        ];

        foreach ($config_names as $config_name) {
          if ($config_change_type == 'rename') {
            $names = $storage_comparer->extractRenameNames($config_name);
            $route_options = ['source_name' => $names['old_name'], 'target_name' => $names['new_name']];
            $config_name = $this->t('@source_name to @target_name', ['@source_name' => $names['old_name'], '@target_name' => $names['new_name']]);
          }
          else {
            $route_options = ['source_name' => $config_name];
          }
          if ($collection != StorageInterface::DEFAULT_COLLECTION) {
            $route_name = 'config.diff_collection';
            $route_options['collection'] = $collection;
          }
          else {
            $route_name = 'config.diff';
          }
          $links['view_diff'] = [
            'title' => $this->t('View differences'),
            'url' => Url::fromRoute($route_name, $route_options),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => json_encode([
                'width' => 700,
              ]),
            ],
          ];
          $form[$collection][$config_change_type]['list']['#rows'][] = [
            'name' => $config_name,
            'operations' => [
              'data' => [
                '#type' => 'operations',
                '#links' => $links,
              ],
            ],
          ];
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
      $this->moduleInstaller,
      $this->themeHandler,
      $this->getStringTranslation(),
      $this->moduleExtensionList,
      $this->themeExtensionList
    );
    if ($config_importer->alreadyImporting()) {
      $this->messenger()->addStatus($this->t('Another request may be synchronizing configuration already.'));
    }
    else {
      try {
        $sync_steps = $config_importer->initialize();
        $batch_builder = (new BatchBuilder())
          ->setTitle($this->t('Synchronizing configuration'))
          ->setFinishCallback([ConfigImporterBatch::class, 'finish'])
          ->setInitMessage($this->t('Starting configuration synchronization.'))
          ->setProgressMessage($this->t('Completed step @current of @total.'))
          ->setErrorMessage($this->t('Configuration synchronization has encountered an error.'));
        foreach ($sync_steps as $sync_step) {
          $batch_builder->addOperation([ConfigImporterBatch::class, 'process'], [$config_importer, $sync_step]);
        }

        batch_set($batch_builder->toArray());
      }
      catch (ConfigImporterException) {
        // There are validation errors.
        $this->messenger()->addError($this->t('The configuration cannot be imported because it failed validation for the following reasons:'));
        foreach ($config_importer->getErrors() as $message) {
          $this->messenger()->addError($message);
        }
      }
    }
  }

}
