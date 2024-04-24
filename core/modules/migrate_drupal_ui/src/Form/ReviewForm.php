<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal\MigrationState;
use Drupal\migrate_drupal_ui\Batch\MigrateUpgradeImportBatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate Upgrade review form.
 *
 * This confirmation form provides the user with a summary of all the modules
 * enabled on the source site and whether they will be upgraded or not. Data
 * from a module's .migrate_drupal.yml file and all the migration plugins
 * (source, destination and field) for each enabled Drupal 8 module are used to
 * decide the migration status for each enabled module on the source site.
 *
 * The migration status displayed on the Review page is a list of all the
 * enabled modules on the source site divided into two categories, those that
 * will not be upgraded and those that will be upgraded. The intention is to
 * provide the admin with enough information to decide if it is OK to proceed
 * with the upgrade.
 *
 * @internal
 */
class ReviewForm extends MigrateUpgradeFormBase {
  use DeprecatedServicePropertyTrait;

  /**
   * The service properties that should raise a deprecation error.
   */
  private array $deprecatedProperties = ['moduleHandler' => 'module_handler'];

  /**
   * The migrations.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface[]
   */
  protected $migrations;

  /**
   * Migration state service.
   *
   * @var \Drupal\migrate_drupal\MigrationState
   */
  protected $migrationState;

  /**
   * Module extension list.
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Source system data set in buildForm().
   *
   * @var array
   */
  protected $systemData;

  /**
   * ReviewForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore_private
   *   The private tempstore factory service.
   * @param \Drupal\migrate_drupal\MigrationState $migrationState
   *   Migration state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleExtensionList|\Drupal\Core\Extension\ModuleHandlerInterface $module_extension_list
   *   The module extension list.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   */
  public function __construct(
    StateInterface $state,
    MigrationPluginManagerInterface $migration_plugin_manager,
    PrivateTempStoreFactory $tempstore_private,
    MigrationState $migrationState,
    ConfigFactoryInterface $config_factory,
    ModuleExtensionList|ModuleHandlerInterface $module_extension_list,
    protected ?TimeInterface $time = NULL,
  ) {
    parent::__construct($config_factory, $migration_plugin_manager, $state, $tempstore_private);
    $this->migrationState = $migrationState;
    if ($this->time === NULL) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $time argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3112298', E_USER_DEPRECATED);
      $this->time = \Drupal::service('datetime.time');
    }
    if ($module_extension_list instanceof ModuleHandlerInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with the $module_extension_list argument as ModuleHandlerInterface is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3310017', E_USER_DEPRECATED);
      $module_extension_list = \Drupal::service('extension.list.module');
    }
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('plugin.manager.migration'),
      $container->get('tempstore.private'),
      $container->get('migrate_drupal.migration_state'),
      $container->get('config.factory'),
      $container->get('extension.list.module'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_drupal_ui_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all the data needed for this form.
    $version = $this->store->get('version');
    $this->migrations = $this->store->get('migrations');
    // Fetch the source system data at the first opportunity.
    $this->systemData = $this->store->get('system_data');

    // If data is missing or this is the wrong step, start over.
    if (!$version || !$this->migrations || !$this->systemData ||
      ($this->store->get('step') != 'review')) {
      return $this->restartUpgradeForm();
    }

    $form = parent::buildForm($form, $form_state);
    $form['#title'] = $this->t('What will be upgraded?');

    $migrations = $this->migrationPluginManager->createInstances(array_keys($this->store->get('migrations')));

    // Get the upgrade states for the source modules.
    $display = $this->migrationState->getUpgradeStates($version, $this->systemData, $migrations);

    // Missing migrations.
    $missing_module_list = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Modules that will not be upgraded'),
      '#summary_attributes' => ['id' => ['error']],
      '#description' => $this->t("The new site is missing modules corresponding to the old site's modules. Unless they are installed prior to the upgrade, configuration and/or content needed by them will not be available on your new site. <a href=':review'>Read the checklist</a> to help decide what to do.", [':review' => 'https://www.drupal.org/docs/8/upgrade/upgrade-using-web-browser#pre-upgrade-analysis']),
      '#weight' => 2,
    ];
    $missing_module_list['module_list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Drupal @version module name', ['@version' => $version]),
        $this->t('Drupal @version machine name', ['@version' => $version]),
        $this->t('Drupal @version', ['@version' => $this->destinationSiteVersion]),
      ],
    ];

    $missing_count = 0;
    if (isset($display[MigrationState::NOT_FINISHED])) {
      $output = $this->prepareOutput($display[MigrationState::NOT_FINISHED]);
      foreach ($output as $data) {
        $missing_count++;
        // Get the migration status for each source module, if a module of the
        // same name exists on the destination site.
        $missing_module_list['module_list']['#rows'][] = [
          [
            'data' => $data['source_module_name'],
            'class' => ['upgrade-analysis-report__status-icon', 'upgrade-analysis-report__status-icon--error'],
          ],
          $data['source_machine_name'],
          $data['destination'],
        ];
      }
    }

    // Available migrations.
    $available_module_list = [
      '#type' => 'details',
      '#title' => $this->t('Modules that will be upgraded'),
      '#summary_attributes' => ['id' => ['checked']],
      '#weight' => 4,
    ];

    $available_module_list['module_list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Drupal @version module name', ['@version' => $version]),
        $this->t('Drupal @version machine name', ['@version' => $version]),
        $this->t('Drupal @version', ['@version' => $this->destinationSiteVersion]),
      ],
    ];

    $available_count = 0;
    if (isset($display[MigrationState::FINISHED])) {
      $output = $this->prepareOutput($display[MigrationState::FINISHED]);
      foreach ($output as $data) {
        $available_count++;
        $available_module_list['module_list']['#rows'][] = [
          [
            'data' => $data['source_module_name'],
            'class' => ['upgrade-analysis-report__status-icon', 'upgrade-analysis-report__status-icon--checked'],
          ],
          $data['source_machine_name'],
          $data['destination'],
        ];
      }
    }

    $counters = [];
    $general_info = [];

    if ($missing_count) {
      $counters[] = [
        '#theme' => 'status_report_counter',
        '#amount' => $missing_count,
        '#text' => $this->formatPlural($missing_count, 'Module will not be upgraded', 'Modules will not be upgraded'),
        '#severity' => 'error',
        '#weight' => 0,
      ];
      $general_info[] = $missing_module_list;
    }
    if ($available_count) {
      $counters[] = [
        '#theme' => 'status_report_counter',
        '#amount' => $available_count,
        '#text' => $this->formatPlural($available_count, 'Module will be upgraded', 'Modules will be upgraded'),
        '#severity' => 'checked',
        '#weight' => 1,
      ];
      $general_info[] = $available_module_list;
    }

    $form['status_report_page'] = [
      '#theme' => 'status_report_page',
      '#counters' => $counters,
      '#general_info' => $general_info,
    ];

    $form['#attached']['library'][] = 'migrate_drupal_ui/base';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config['source_base_path'] = $this->store->get('source_base_path');
    $config['source_private_file_path'] = $this->store->get('source_private_file_path');
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Running upgrade'))
      ->setProgressMessage('')
      ->addOperation([
        MigrateUpgradeImportBatch::class,
        'run',
      ], [array_keys($this->migrations), $config])
      ->setFinishCallback([MigrateUpgradeImportBatch::class, 'finished']);
    batch_set($batch_builder->toArray());
    $form_state->setRedirect('<front>');
    $this->store->set('step', 'overview');
    $this->state->set('migrate_drupal_ui.performed', $this->time->getRequestTime());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Perform upgrade');
  }

  /**
   * Prepare the migration state data for output.
   *
   * Each source and destination module_name is changed to the human-readable
   * name, the destination modules are put into a CSV format, and everything is
   * sorted.
   *
   * @param string[] $migration_state
   *   An array where the keys are machine names of modules on
   *   the source site. Values are lists of machine names of modules on the
   *   destination site, in CSV format.
   *
   * @return string[][]
   *   An indexed array of arrays that contain module data, sorted by the source
   *   module name. Each sub-array contains the source module name, the source
   *   module machine name, and the destination module names in a sorted CSV
   *   format.
   */
  protected function prepareOutput(array $migration_state) {
    $output = [];
    foreach ($migration_state as $source_machine_name => $destination_modules) {
      $data = NULL;
      if (isset($this->systemData['module'][$source_machine_name]['info'])) {
        $data = unserialize($this->systemData['module'][$source_machine_name]['info']);
      }
      $source_module_name = $data['name'] ?? $source_machine_name;
      // Get the names of all the destination modules.
      $destination_module_names = [];
      if (!empty($destination_modules)) {
        $destination_modules = explode(', ', $destination_modules);
        foreach ($destination_modules as $destination_module) {
          if ($destination_module === 'core') {
            $destination_module_names[] = 'Core';
          }
          else {
            try {
              $destination_module_names[] = $this->moduleExtensionList->getName($destination_module);
            }
            catch (UnknownExtensionException $e) {
              $destination_module_names[] = $destination_module;
            }
          }
        }
      }
      sort($destination_module_names);
      $output[$source_machine_name] = [
        'source_module_name' => $source_module_name,
        'source_machine_name' => $source_machine_name,
        'destination' => implode(', ', $destination_module_names),
      ];
    }
    usort($output, function ($a, $b) {
      return strcmp($a['source_module_name'], $b['source_module_name']);
    });
    return $output;
  }

}
