<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\migrate\Audit\IdAuditor;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Drupal\migrate_drupal_ui\Batch\MigrateUpgradeImportBatch;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a multi-step form for performing direct site upgrades.
 *
 * @internal
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
   * The field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
      'i18n',
      'i18nstrings',
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
      'toolbar',
      'translation',
      'trigger',
      'views_content',
      'views_ui',
    ],
  ];

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
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $field_plugin_manager
   *   The field plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(StateInterface $state, DateFormatterInterface $date_formatter, RendererInterface $renderer, MigrationPluginManagerInterface $plugin_manager, MigrateFieldPluginManagerInterface $field_plugin_manager, ModuleHandlerInterface $module_handler) {
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->pluginManager = $plugin_manager;
    $this->fieldPluginManager = $field_plugin_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.field'),
      $container->get('module_handler')
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

      case 'confirm_id_conflicts':
        return $this->buildIdConflictForm($form, $form_state);

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
        '#markup' => '<p>' . $this->t('Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal 8. See the <a href=":url">Drupal site upgrades handbook</a> for more information.', [
          ':url' => 'https://www.drupal.org/upgrade/migrate',
        ]),
      ];

      $form['legend']['#markup'] = '';
      $form['legend']['#markup'] .= '<h3>' . $this->t('Definitions') . '</h3>';
      $form['legend']['#markup'] .= '<dl>';
      $form['legend']['#markup'] .= '<dt>' . $this->t('Old site') . '</dt>';
      $form['legend']['#markup'] .= '<dd>' . $this->t('The site you want to upgrade.') . '</dd>';
      $form['legend']['#markup'] .= '<dt>' . $this->t('New site') . '</dt>';
      $form['legend']['#markup'] .= '<dd>' . $this->t('This empty Drupal 8 installation you will import the old site to.') . '</dd>';
      $form['legend']['#markup'] .= '</dl>';

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
        '#title' => $this->t('Preparation steps'),
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
      '#options' => ['6' => $this->t('Drupal 6'), '7' => $this->t('Drupal 7')],
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
          ':input[name="version"]' => ['value' => '6'],
        ],
      ],
    ];

    $form['source']['source_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public files directory'),
      '#description' => $this->t('To import public files from your current Drupal site, enter a local file directory containing your site (e.g. /var/www/docroot), or your site address (for example http://example.com).'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => ['value' => '7'],
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
          ':input[name="version"]' => ['value' => '7'],
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
      $version = (string) $this->getLegacyDrupalVersion($connection);
      if (!$version) {
        $form_state->setErrorByName($database['driver'] . '][0', $this->t('Source database does not contain a recognizable Drupal version.'));
      }
      elseif ($version !== (string) $form_state->getValue('version')) {
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
        if ($version === '6') {
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
    $form_state->set('step', 'confirm_id_conflicts');
    $form_state->setRebuild();
  }

  /**
   * Confirmation form for ID conflicts.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildIdConflictForm(array &$form, FormStateInterface $form_state) {
    // Check if there are conflicts. If none, just skip this form!
    $migration_ids = array_keys($form_state->get('migrations'));
    $migrations = $this->pluginManager->createInstances($migration_ids);

    $translated_content_conflicts = $content_conflicts = [];

    $results = (new IdAuditor())->auditMultiple($migrations);

    /** @var \Drupal\migrate\Audit\AuditResult $result */
    foreach ($results as $result) {
      $destination = $result->getMigration()->getDestinationPlugin();
      if ($destination instanceof EntityContentBase && $destination->isTranslationDestination()) {
        // Translations are not yet supperted by the audit system. For now, we
        // only warn the user to be cautious when migrating translated content.
        // I18n support should be added in https://www.drupal.org/node/2905759.
        $translated_content_conflicts[] = $result;
      }
      elseif (!$result->passed()) {
        $content_conflicts[] = $result;
      }

    }
    if (empty($content_conflicts) && empty($translated_content_conflicts)) {
      $form_state->set('step', 'confirm');
      return $this->buildForm($form, $form_state);
    }

    drupal_set_message($this->t('WARNING: Content may be overwritten on your new site.'), 'warning');

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#submit'] = ['::submitConfirmIdConflictForm'];
    $form['actions']['submit']['#value'] = $this->t('I acknowledge I may lose data. Continue anyway.');

    if ($content_conflicts) {
      $form = $this->conflictsForm($form, $form_state, $content_conflicts);
    }
    if ($translated_content_conflicts) {
      $form = $this->i18nWarningForm($form, $form_state, $translated_content_conflicts);
    }
    return $form;
  }

  /**
   * Build the markup for conflict warnings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\migrate\Audit\AuditResult[] $conflicts
   *   The failing audit results.
   *
   * @return array
   *   The form structure.
   */
  protected function conflictsForm(array &$form, FormStateInterface $form_state, array $conflicts) {
    $form['conflicts'] = [
      '#title' => $this->t('There is conflicting content of these types:'),
      '#theme' => 'item_list',
      '#items' => $this->formatConflicts($conflicts),
    ];

    $form['warning'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('It looks like you have content on your new site which <strong>may be overwritten</strong> if you continue to run this upgrade. The upgrade should be performed on a clean Drupal 8 installation. For more information see the <a target="_blank" href=":id-conflicts-handbook">upgrade handbook</a>.', [':id-conflicts-handbook' => 'https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#id_conflicts']) . '</p>',
    ];

    return $form;
  }

  /**
   * Formats a set of failing audit results as strings.
   *
   * Each string is the label of the destination plugin of the migration that
   * failed the audit, keyed by the destination plugin ID in order to prevent
   * duplication.
   *
   * @param \Drupal\migrate\Audit\AuditResult[] $conflicts
   *   The failing audit results.
   *
   * @return string[]
   *   The formatted audit results.
   */
  protected function formatConflicts(array $conflicts) {
    $items = [];

    foreach ($conflicts as $conflict) {
      $definition = $conflict->getMigration()->getDestinationPlugin()->getPluginDefinition();
      $id = $definition['id'];
      $items[$id] = $definition['label'];
    }
    sort($items, SORT_STRING);

    return $items;
  }

  /**
   * Build the markup for i18n warnings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\migrate\Audit\AuditResult[] $conflicts
   *   The failing audit results.
   *
   * @return array
   *   The form structure.
   */
  protected function i18nWarningForm(array &$form, FormStateInterface $form_state, array $conflicts) {
    $form['i18n'] = [
      '#title' => $this->t('There is translated content of these types:'),
      '#theme' => 'item_list',
      '#items' => $this->formatConflicts($conflicts),
    ];

    $form['i18n_warning'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('It looks like you are migrating translated content from your old site. Possible ID conflicts for translations are not automatically detected in the current version of Drupal. Refer to the <a target="_blank" href=":id-conflicts-handbook">upgrade handbook</a> for instructions on how to avoid ID conflicts with translated content.', [':id-conflicts-handbook' => 'https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#id_conflicts']) . '</p>',
    ];

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
  public function submitConfirmIdConflictForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('step', 'confirm');
    $form_state->setRebuild();
  }

  /**
   * Confirmation form showing available and missing migration paths.
   *
   * The confirmation form uses the source_module and destination_module
   * properties on the source, destination and field plugins as well as the
   * system data from the source to determine if there is a migration path for
   * each module in the source.
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

    // Get the source_module and destination_module for each migration.
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

    // Fetch the system data at the first opportunity.
    $system_data = $form_state->get('system_data');

    // Add source_module and destination_module for modules that do not need an
    // upgrade path and are enabled on the source site.
    foreach ($this->noUpgradePaths[$version] as $extension) {
      if ($system_data['module'][$extension]['status']) {
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
