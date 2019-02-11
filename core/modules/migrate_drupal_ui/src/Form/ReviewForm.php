<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal_ui\Batch\MigrateUpgradeImportBatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate Upgrade review form.
 *
 * This confirmation form uses the source_module and destination_module
 * properties on the source, destination and field plugins as well as the
 * system data from the source to determine if there is a migration path for
 * each module in the source.
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
   * List of extensions that do not need an upgrade path.
   *
   * This property is an array where the keys are the major Drupal core version
   * from which we are upgrading, and the values are arrays of extension names
   * that do not need an upgrade path.
   *
   * @var array[]
   */
  protected $noUpgradePaths = [
    '6' => [
      'blog',
      'blogapi',
      'calendarsignup',
      'color',
      'content_copy',
      'content_multigroup',
      'content_permissions',
      'date_api',
      'date_locale',
      'date_php4',
      'date_popup',
      'date_repeat',
      'date_timezone',
      'date_tools',
      'datepicker',
      'ddblock',
      'event',
      'fieldgroup',
      'filefield_meta',
      'help',
      'i18nstrings',
      'i18nsync',
      'imageapi',
      'imageapi_gd',
      'imageapi_imagemagick',
      'imagecache_ui',
      'jquery_ui',
      'nodeaccess',
      'number',
      'openid',
      'php',
      'ping',
      'poll',
      'throttle',
      'tracker',
      'translation',
      'trigger',
      'variable',
      'variable_admin',
      'views_export',
      'views_ui',
    ],
    '7' => [
      'blog',
      'bulk_export',
      'contextual',
      'ctools',
      'ctools_access_ruleset',
      'ctools_ajax_sample',
      'ctools_custom_content',
      'dashboard',
      'date_all_day',
      'date_api',
      'date_context',
      'date_migrate',
      'date_popup',
      'date_repeat',
      'date_repeat_field',
      'date_tools',
      'date_views',
      'entity',
      'entity_feature',
      'entity_token',
      'entityreference',
      'field_ui',
      'help',
      'openid',
      'overlay',
      'page_manager',
      'php',
      'poll',
      'search_embedded_form',
      'search_extra_type',
      'search_node_tags',
      'simpletest',
      'stylizer',
      'term_depth',
      'title',
      'toolbar',
      'translation',
      'trigger',
      'views_content',
      'views_ui',
    ],
  ];

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
   */
  public function __construct(StateInterface $state, MigrationPluginManagerInterface $migration_plugin_manager, MigrateFieldPluginManagerInterface $field_plugin_manager, PrivateTempStoreFactory $tempstore_private) {
    parent::__construct($tempstore_private);
    $this->state = $state;
    $this->pluginManager = $migration_plugin_manager;
    $this->fieldPluginManager = $field_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.field'),
      $container->get('tempstore.private')
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
    // Fetch the system data at the first opportunity.
    $system_data = $this->store->get('system_data');

    // If data is missing or this is the wrong step, start over.
    if (!$version || !$this->migrations || !$system_data ||
      ($this->store->get('step') != 'review')) {
      return $this->restartUpgradeForm();
    }

    $form = parent::buildForm($form, $form_state);
    $form['#title'] = $this->t('What will be upgraded?');

    // Get the source_module and destination_module for each migration.
    $migrations = $this->pluginManager->createInstances(array_keys($this->store->get('migrations')));
    $table_data = [];
    foreach ($migrations as $migration) {
      $migration_id = $migration->getPluginId();
      $source_module = $migration->getSourcePlugin()->getSourceModule();
      if (!$source_module) {
        $this->messenger()->addError($this->t('Source module not found for @migration_id.', ['@migration_id' => $migration_id]));
      }
      $destination_module = $migration->getDestinationPlugin()->getDestinationModule();
      if (!$destination_module) {
        $this->messenger()->addError($this->t('Destination module not found for @migration_id.', ['@migration_id' => $migration_id]));
      }

      if ($source_module && $destination_module) {
        $table_data[$source_module][$destination_module][$migration_id] = $migration->label();
      }
    }

    // Get the source_module and destination_module from the field plugins.
    $definitions = $this->fieldPluginManager->getDefinitions();
    foreach ($definitions as $definition) {
      // This is not strict so that we find field plugins with an annotation
      // where the Drupal core version is an integer and when it is a string.
      if (in_array($version, $definition['core'])) {
        $source_module = $definition['source_module'];
        $destination_module = $definition['destination_module'];
        $table_data[$source_module][$destination_module][$definition['id']] = $definition['id'];
      }
    }

    // Add source_module and destination_module for modules that do not need an
    // upgrade path and are enabled on the source site.
    foreach ($this->noUpgradePaths[$version] as $extension) {
      if (isset($system_data['module'][$extension]) && $system_data['module'][$extension]['status']) {
        $table_data[$extension]['core'][$extension] = $extension;
      }
    }

    // Sort the table by source module names and within that destination
    // module names.
    ksort($table_data);
    foreach ($table_data as $source_module => $destination_module_info) {
      ksort($table_data[$source_module]);
    }

    // Remove core profiles from the system data.
    foreach (['standard', 'minimal'] as $profile) {
      unset($system_data['module'][$profile]);
    }

    $unmigrated_source_modules = array_diff_key($system_data['module'], $table_data);

    // Missing migrations.
    $missing_module_list = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Modules that will not be upgraded'),
      '#summary_attributes' => ['id' => ['error']],
      '#description' => $this->t('There are no modules installed on your new site to replace these modules. If you proceed with the upgrade now, configuration and/or content needed by these modules will not be available on your new site. For more information, see <a href=":review">Review the pre-upgrade analysis</a> in the <a href=":migrate">Upgrading to Drupal 8</a> handbook.', [':review' => 'https://www.drupal.org/docs/8/upgrade/upgrade-using-web-browser#pre-upgrade-analysis', ':migrate' => 'https://www.drupal.org/docs/8/upgrade']),
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
    ksort($unmigrated_source_modules);
    foreach ($unmigrated_source_modules as $source_module => $module_data) {
      if ($module_data['status']) {
        $missing_count++;
        $missing_module_list['module_list'][$source_module] = [
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
          'destination_module' => ['#plain_text' => 'Not upgraded'],
        ];
      }
    }

    // Available migrations.
    $available_module_list = [
      '#type' => 'details',
      '#title' => $this->t('Modules that will be upgraded'),
      '#summary_attributes' => ['id' => ['checked']],
      '#weight' => 3,
    ];

    $available_module_list['module_list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Drupal @version', ['@version' => $version]),
        $this->t('Drupal 8'),
      ],
    ];

    $available_count = 0;
    foreach ($table_data as $source_module => $destination_module_info) {
      $available_count++;
      $destination_details = [];
      foreach ($destination_module_info as $destination_module => $migration_ids) {
        $destination_details[$destination_module] = [
          '#type' => 'item',
          '#plain_text' => $destination_module,
        ];
      }
      $available_module_list['module_list'][$source_module] = [
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
        'destination_module' => $destination_details,
      ];
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
