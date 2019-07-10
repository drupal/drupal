<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal\MigrationState;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
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

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The field plugin manager service.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

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
   * ReviewForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $field_plugin_manager
   *   The field plugin manager service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore_private
   *   The private tempstore factory.
   * @param \Drupal\migrate_drupal\MigrationState $migrationState
   *   Migration state service.
   */
  public function __construct(StateInterface $state, MigrationPluginManagerInterface $migration_plugin_manager, MigrateFieldPluginManagerInterface $field_plugin_manager, PrivateTempStoreFactory $tempstore_private, MigrationState $migrationState) {
    parent::__construct($tempstore_private);
    $this->state = $state;
    $this->pluginManager = $migration_plugin_manager;
    $this->fieldPluginManager = $field_plugin_manager;
    $this->migrationState = $migrationState;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.field'),
      $container->get('tempstore.private'),
      $container->get('migrate_drupal.migration_state')
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
    $system_data = $this->store->get('system_data');

    // If data is missing or this is the wrong step, start over.
    if (!$version || !$this->migrations || !$system_data ||
      ($this->store->get('step') != 'review')) {
      return $this->restartUpgradeForm();
    }

    $form = parent::buildForm($form, $form_state);
    $form['#title'] = $this->t('What will be upgraded?');

    $migrations = $this->pluginManager->createInstances(array_keys($this->store->get('migrations')));

    // Get the upgrade states for the source modules.
    $display = $this->migrationState->getUpgradeStates($version, $system_data, $migrations);

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
        $this->t('Drupal @version', ['@version' => $version]),
        $this->t('Drupal 8'),
      ],
    ];

    $missing_count = 0;
    if (isset($display[MigrationState::NOT_FINISHED])) {
      foreach ($display[MigrationState::NOT_FINISHED] as $source_module => $destination_modules) {
        $missing_count++;
        // Get the migration status for this $source_module, if a module of the
        // same name exists on the destination site.
        $missing_module_list['module_list'][] = [
          'source_module' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $source_module,
            '#attributes' => [
              'class' => [
                'upgrade-analysis-report__status-icon',
                'upgrade-analysis-report__status-icon--error',
              ],
            ],
          ],
          'destination_module' => [
            '#plain_text' => $destination_modules,
          ],
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
        $this->t('Drupal @version', ['@version' => $version]),
        $this->t('Drupal 8'),
      ],
    ];

    $available_count = 0;
    if (isset($display[MigrationState::FINISHED])) {
      foreach ($display[MigrationState::FINISHED] as $source_module => $destination_modules) {
        $available_count++;
        $available_module_list['module_list'][] = [
          'source_module' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $source_module,
            '#attributes' => [
              'class' => [
                'upgrade-analysis-report__status-icon',
                'upgrade-analysis-report__status-icon--checked',
              ],
            ],
          ],
          'destination_module' => [
            '#plain_text' => $destination_modules,
          ],
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
    $batch = [
      'title' => $this->t('Running upgrade'),
      'progress_message' => '',
      'operations' => [
        [
          [MigrateUpgradeImportBatch::class, 'run'],
          [array_keys($this->migrations), $config],
        ],
      ],
      'finished' => [
        MigrateUpgradeImportBatch::class, 'finished',
      ],
    ];
    batch_set($batch);
    $form_state->setRedirect('<front>');
    $this->store->set('step', 'overview');
    $this->state->set('migrate_drupal_ui.performed', REQUEST_TIME);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Perform upgrade');
  }

}
