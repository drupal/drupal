<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal_ui\Batch\MigrateUpgradeImportBatch;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a multi-step form for performing direct site upgrades.
 */
class MigrateUpgradeForm extends ConfirmFormBase {

  use MigrationConfigurationTrait;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs the MigrateUpgradeForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(StateInterface $state, DateFormatterInterface $date_formatter, RendererInterface $renderer, MigrationPluginManagerInterface $plugin_manager) {
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_drupal_ui_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?: 'overview';
    switch ($step) {
      case 'overview':
        return $this->buildOverviewForm($form, $form_state);

      case 'credentials':
        return $this->buildCredentialForm($form, $form_state);

      case 'confirm':
        return $this->buildConfirmForm($form, $form_state);

      default:
        drupal_set_message($this->t('Unrecognized form step @step', ['@step' => $step]), 'error');
        return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is intentionally empty, see the specific submit methods for
    // each form step.
  }

  /**
   * Builds the form presenting an overview of the migration process.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildOverviewForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Upgrade');

    if ($date_performed = $this->state->get('migrate_drupal_ui.performed')) {
      // @todo Add back support for rollbacks and incremental migrations.
      //   https://www.drupal.org/node/2687843
      //   https://www.drupal.org/node/2687849
      $form['upgrade_option_item'] = [
        '#type' => 'item',
        '#prefix' => $this->t('An upgrade has already been performed on this site. To perform a new migration, create a clean and empty new install of Drupal 8. Rollbacks and incremental migrations are not yet supported through the user interface. For more information, see the <a href=":url">upgrading handbook</a>.', [':url' => 'https://www.drupal.org/upgrade/migrate']),
        '#description' => $this->t('Last upgrade: @date', ['@date' => $this->dateFormatter->format($date_performed)]),
      ];
      return $form;
    }
    else {
      $form['info_header'] = [
        '#markup' => '<p>' . $this->t('Upgrade a site by importing its database and files into a clean and empty new install of Drupal 8. See the <a href=":url">Drupal site upgrades handbook</a> for more information.', [
          ':url' => 'https://www.drupal.org/upgrade/migrate',
        ]),
      ];

      $legend[] = $this->t('<em>Old site:</em> the site you want to upgrade.');
      $legend[] = $this->t('<em>New site:</em> this empty Drupal 8 installation you will import the old site to.');

      $form['legend'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Definitions'),
        '#list_type' => 'ul',
        '#items' => $legend,
      ];

      $info[] = $this->t('You may need multiple tries for a successful upgrade so <strong>backup the database</strong> for this new site. The upgrade will change it and you may want to revert to its initial state.');
      $info[] = $this->t('Make sure that <strong>access to the database</strong> for the old site is available from this new site.');
      $info[] = $this->t('<strong>If the old site has private files</strong>, a copy of its files directory must also be accessible on the host of this new site.');
      $info[] = $this->t('<strong>Enable all modules on this new site</strong> that are enabled on the old site. For example, if the old site uses the book module, then enable the book module on this new site so that the existing data can be imported to it.');
      $info[] = $this->t('<strong>Do not add any content to the new site</strong> before upgrading. Any existing content is likely to be overwritten by the upgrade process. See <a href=":url">the upgrade preparation guide</a>.', [
        ':url' => 'https://www.drupal.org/docs/8/upgrade/preparing-an-upgrade#dont_create_content',
      ]);
      $info[] = $this->t('Put this site into <a href=":url">maintenance mode</a>.', [
        ':url' => Url::fromRoute('system.site_maintenance_mode')->toString(TRUE)->getGeneratedUrl(),
      ]);

      $form['info'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Steps to prepare for the upgrade'),
        '#list_type' => 'ol',
        '#items' => $info,
      ];

      $form['info_footer'] = [
        '#markup' => '<p>' . $this->t('The upgrade can take a long time. It is better to upgrade from a local copy of your site instead of directly from your live site.'),
      ];

      $validate = [];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#button_type' => 'primary',
      '#validate' => $validate,
      '#submit' => ['::submitOverviewForm'],
    ];
    return $form;
  }

  /**
   * Form submission handler for the overview form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitOverviewForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('step', 'credentials');
    $form_state->setRebuild();
  }

  /**
   * Builds the database credential form and adds file location information.
   *
   * This is largely borrowed from \Drupal\Core\Installer\Form\SiteSettingsForm.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   *
   * @todo Private files directory not yet implemented, depends on
   *   https://www.drupal.org/node/2547125.
   */
  public function buildCredentialForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Drupal Upgrade');

    $drivers = $this->getDatabaseTypes();
    $drivers_keys = array_keys($drivers);
    // @todo https://www.drupal.org/node/2678510 Because this is a multi-step
    //   form, the form is not rebuilt during submission. Ideally we would get
    //   the chosen driver from form input, if available, in order to use
    //   #limit_validation_errors in the same way
    //   \Drupal\Core\Installer\Form\SiteSettingsForm does.
    $default_driver = current($drivers_keys);

    $default_options = [];


    $form['version'] = [
      '#type' => 'radios',
      '#default_value' => 7,
      '#title' => $this->t('Drupal version of the source site'),
      '#options' => [6 => $this->t('Drupal 6'), 7 => $this->t('Drupal 7')],
      '#required' => TRUE,
    ];

    $form['database'] = [
      '#type' => 'details',
      '#title' => $this->t('Source database'),
      '#description' => $this->t('Provide credentials for the database of the Drupal site you want to upgrade.'),
      '#open' => TRUE,
    ];

    $form['database']['driver'] = [
      '#type' => 'radios',
      '#title' => $this->t('Database type'),
      '#required' => TRUE,
      '#default_value' => $default_driver,
    ];
    if (count($drivers) == 1) {
      $form['database']['driver']['#disabled'] = TRUE;
    }

    // Add driver-specific configuration options.
    foreach ($drivers as $key => $driver) {
      $form['database']['driver']['#options'][$key] = $driver->name();

      $form['database']['settings'][$key] = $driver->getFormOptions($default_options);
      // @todo https://www.drupal.org/node/2678510 Using
      //   #limit_validation_errors in the submit does not work so it is not
      //   possible to require the database and username for mysql and pgsql.
      //   This is because this is a multi-step form.
      $form['database']['settings'][$key]['database']['#required'] = FALSE;
      $form['database']['settings'][$key]['username']['#required'] = FALSE;
      $form['database']['settings'][$key]['#prefix'] = '<h2 class="js-hide">' . $this->t('@driver_name settings', ['@driver_name' => $driver->name()]) . '</h2>';
      $form['database']['settings'][$key]['#type'] = 'container';
      $form['database']['settings'][$key]['#tree'] = TRUE;
      $form['database']['settings'][$key]['advanced_options']['#parents'] = [$key];
      $form['database']['settings'][$key]['#states'] = [
        'visible' => [
          ':input[name=driver]' => ['value' => $key],
        ],
      ];

      // Move the host fields out of advanced settings.
      if (isset($form['database']['settings'][$key]['advanced_options']['host'])) {
        $form['database']['settings'][$key]['host'] = $form['database']['settings'][$key]['advanced_options']['host'];
        $form['database']['settings'][$key]['host']['#title'] = 'Database host';
        $form['database']['settings'][$key]['host']['#weight'] = -1;
        unset($form['database']['settings'][$key]['database']['#default_value']);
        unset($form['database']['settings'][$key]['advanced_options']['host']);
      }
    }

    $form['source'] = [
      '#type' => 'details',
      '#title' => $this->t('Source files'),
      '#open' => TRUE,
    ];
    $form['source']['d6_source_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Files directory'),
      '#description' => $this->t('To import files from your current Drupal site, enter a local file directory containing your site (e.g. /var/www/docroot), or your site address (for example http://example.com).'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => ['value' => 6],
        ],
      ],
    ];

    $form['source']['source_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public files directory'),
      '#description' => $this->t('To import public files from your current Drupal site, enter a local file directory containing your site (e.g. /var/www/docroot), or your site address (for example http://example.com).'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => ['value' => 7],
        ],
      ],
    ];

    $form['source']['source_private_file_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private file directory'),
      '#default_value' => '',
      '#description' => $this->t('To import private files from your current Drupal site, enter a local file directory containing your site (e.g. /var/www/docroot).'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => ['value' => 7],
        ],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Review upgrade'),
      '#button_type' => 'primary',
      '#validate' => ['::validateCredentialForm'],
      '#submit' => ['::submitCredentialForm'],
    ];
    return $form;
  }

  /**
   * Validation handler for the credentials form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateCredentialForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the database driver from the form, use reflection to get the
    // namespace, and then construct a valid database array the same as in
    // settings.php.
    $driver = $form_state->getValue('driver');
    $drivers = $this->getDatabaseTypes();
    $reflection = new \ReflectionClass($drivers[$driver]);
    $install_namespace = $reflection->getNamespaceName();

    $database = $form_state->getValue($driver);
    // Cut the trailing \Install from namespace.
    $database['namespace'] = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
    $database['driver'] = $driver;

    // Validate the driver settings and just end here if we have any issues.
    if ($errors = $drivers[$driver]->validateDatabaseSettings($database)) {
      foreach ($errors as $name => $message) {
        $form_state->setErrorByName($name, $message);
      }
      return;
    }

    try {
      $connection = $this->getConnection($database);
      $version = $this->getLegacyDrupalVersion($connection);
      if (!$version) {
        $form_state->setErrorByName($database['driver'] . '][0', $this->t('Source database does not contain a recognizable Drupal version.'));
      }
      elseif ($version != $form_state->getValue('version')) {
        $form_state->setErrorByName($database['driver'] . '][0', $this->t('Source database is Drupal version @version but version @selected was selected.', [
          '@version' => $version,
          '@selected' => $form_state->getValue('version'),
        ]));
      }
      else {
        $this->createDatabaseStateSettings($database, $version);
        $migrations = $this->getMigrations('migrate_drupal_' . $version, $version);

        // Get the system data from source database.
        $system_data = $this->getSystemData($connection);

        // Convert the migration object into array
        // so that it can be stored in form storage.
        $migration_array = [];
        foreach ($migrations as $migration) {
          $migration_array[$migration->id()] = $migration->label();
        }

        // Store the retrieved migration IDs in form storage.
        $form_state->set('version', $version);
        $form_state->set('migrations', $migration_array);
        if ($version == 6) {
          $form_state->set('source_base_path', $form_state->getValue('d6_source_base_path'));
        }
        else {
          $form_state->set('source_base_path', $form_state->getValue('source_base_path'));
        }
        $form_state->set('source_private_file_path', $form_state->getValue('source_private_file_path'));
        // Store the retrived system data in form storage.
        $form_state->set('system_data', $system_data);
      }
    }
    catch (\Exception $e) {
      $error_message = [
        '#title' => $this->t('Resolve the issue below to continue the upgrade.'),
        '#theme' => 'item_list',
        '#items' => [$e->getMessage()],
      ];
      $form_state->setErrorByName($database['driver'] . '][0', $this->renderer->renderPlain($error_message));
    }
  }

  /**
   * Submission handler for the credentials form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitCredentialForm(array &$form, FormStateInterface $form_state) {
    // Indicate the next step is confirmation.
    $form_state->set('step', 'confirm');
    $form_state->setRebuild();
  }

  /**
   * Confirmation form for missing migrations, etc.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfirmForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#submit'] = ['::submitConfirmForm'];

    $form['actions']['submit']['#value'] = $this->t('Perform upgrade');

    $version = $form_state->get('version');
    $migrations = $this->getMigrations('migrate_drupal_' . $version, $version);

    $table_data = [];
    foreach ($migrations as $migration) {
      $migration_id = $migration->getPluginId();
      $source_module = $migration->getSourcePlugin()->getSourceModule();
      if (!$source_module) {
        drupal_set_message($this->t('Source module not found for @migration_id.', ['@migration_id' => $migration_id]), 'error');
      }
      $destination_module = $migration->getDestinationPlugin()->getDestinationModule();
      if (!$destination_module) {
        drupal_set_message($this->t('Destination module not found for @migration_id.', ['@migration_id' => $migration_id]), 'error');
      }

      if ($source_module && $destination_module) {
        $table_data[$source_module][$destination_module][$migration_id] = $migration->label();
      }
    }
    // Sort the table by source module names and within that destination
    // module names.
    ksort($table_data);
    foreach ($table_data as $source_module => $destination_module_info) {
      ksort($table_data[$source_module]);
    }

    // Fetch the system data at the first opportunity.
    $system_data = $form_state->get('system_data');
    $unmigrated_source_modules = array_diff_key($system_data['module'], $table_data);

    // Missing migrations.
    $missing_module_list = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('Missing upgrade paths'),
        '#attributes' => ['id' => ['warning']],
      ],
      '#description' => $this->t('The following items will not be upgraded. For more information see <a href=":migrate">Upgrading from Drupal 6 or 7 to Drupal 8</a>.', [':migrate' => 'https://www.drupal.org/upgrade/migrate']),
      '#weight' => 2,
    ];
    $missing_module_list['module_list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source module: Drupal @version', ['@version' => $version]),
        $this->t('Upgrade module: Drupal 8'),
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
                'upgrade-analysis-report__status-icon--warning',
              ],
            ],
          ],
          'destination_module' => ['#plain_text' => 'Missing'],
        ];
      }
    }
    // Available migrations.
    $available_module_list = [
      '#type' => 'details',
      '#title' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('Available upgrade paths'),
        '#attributes' => ['id' => ['checked']],
      ],
      '#weight' => 3,
    ];

    $available_module_list['module_list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source module: Drupal @version', ['@version' => $version]),
        $this->t('Upgrade module: Drupal 8'),
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
        '#text' => $this->formatPlural($missing_count, 'Missing upgrade path', 'Missing upgrade paths'),
        '#severity' => 'warning',
        '#weight' => 0,
      ];
      $general_info[] = $missing_module_list;
    }
    if ($available_count) {
      $counters[] = [
        '#theme' => 'status_report_counter',
        '#amount' => $available_count,
        '#text' => $this->formatPlural($available_count, 'Available upgrade path', 'Available upgrade paths'),
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
   * Submission handler for the confirmation form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitConfirmForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();

    $migrations = $storage['migrations'];
    $config['source_base_path'] = $storage['source_base_path'];
    $batch = [
      'title' => $this->t('Running upgrade'),
      'progress_message' => '',
      'operations' => [
        [
          [MigrateUpgradeImportBatch::class, 'run'],
          [array_keys($migrations), $config],
        ],
      ],
      'finished' => [
        MigrateUpgradeImportBatch::class, 'finished',
      ],
    ];
    batch_set($batch);
    $form_state->setRedirect('<front>');
    $this->state->set('migrate_drupal_ui.performed', REQUEST_TIME);
  }

  /**
   * Returns all supported database driver installer objects.
   *
   * @return \Drupal\Core\Database\Install\Tasks[]
   *   An array of available database driver installer objects.
   */
  protected function getDatabaseTypes() {
    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    return drupal_get_database_types();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Upgrade analysis report');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('migrate_drupal_ui.upgrade');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // The description is added by the buildConfirmForm() method.
    // @see \Drupal\migrate_drupal_ui\Form\MigrateUpgradeForm::buildConfirmForm()
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Perform upgrade');
  }

}
