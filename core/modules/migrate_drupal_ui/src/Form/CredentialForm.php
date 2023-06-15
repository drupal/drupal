<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate Upgrade database credential form.
 *
 * @internal
 */
class CredentialForm extends MigrateUpgradeFormBase {

  /**
   * The HTTP client to fetch the files with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * An array of error information.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * CredentialForm constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore_private
   *   The private tempstore factory service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(PrivateTempStoreFactory $tempstore_private, ClientInterface $http_client, ConfigFactoryInterface $config_factory, MigrationPluginManagerInterface $migration_plugin_manager, StateInterface $state) {
    parent::__construct($config_factory, $migration_plugin_manager, $state, $tempstore_private);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('plugin.manager.migration'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_drupal_ui_credential_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->store->get('step') != 'credential') {
      return $this->restartUpgradeForm();
    }

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Review upgrade');

    $form['#title'] = $this->t('Drupal Upgrade');

    $drivers = $this->getDatabaseTypes();
    $drivers_keys = array_keys($drivers);
    $default_driver = current($drivers_keys);

    $default_options = [];

    $form['help'] = [
      '#type' => 'item',
      '#description' => $this->t('Provide the information to access the Drupal site you want to upgrade. Files can be imported into the upgraded site as well.  See the <a href=":url">Upgrade documentation for more detailed instructions</a>.', [':url' => 'https://www.drupal.org/upgrade/migrate']),
    ];

    $migrate_source_version = Settings::get('migrate_source_version') == '6' ? '6' : '7';
    $form['version'] = [
      '#type' => 'radios',
      '#default_value' => $migrate_source_version,
      '#title' => $this->t('Drupal version of the source site'),
      '#options' => ['6' => $this->t('Drupal 6'), '7' => $this->t('Drupal 7')],
      '#required' => TRUE,
    ];

    $available_connections = array_diff(array_keys(Database::getAllConnectionInfo()), ['default']);
    $options = array_combine($available_connections, $available_connections);
    $migrate_source_connection = Settings::get('migrate_source_connection');
    $preferred_connections = $migrate_source_connection
      ? ['migrate', $migrate_source_connection]
      : ['migrate'];
    $default_options = array_intersect($preferred_connections, $available_connections);
    $form['source_connection'] = [
      '#type' => 'select',
      '#title' => $this->t('Source connection'),
      '#options' => $options,
      '#default_value' => array_pop($default_options),
      '#empty_option' => $this->t('- User defined -'),
      '#description' => $this->t('Choose one of the keys from the $databases array or else select "User defined" and enter database credentials.'),
      '#access' => !empty($options),
    ];

    $form['database'] = [
      '#type' => 'details',
      '#title' => $this->t('Source database'),
      '#description' => $this->t('Provide credentials for the database of the Drupal site you want to upgrade.'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name=source_connection]' => ['value' => ''],
        ],
      ],
    ];

    $form['database']['driver'] = [
      '#type' => 'radios',
      '#title' => $this->t('Database type'),
      '#required' => TRUE,
      '#default_value' => $default_driver,
      '#states' => [
        'required' => [
          ':input[name=source_connection]' => ['value' => ''],
        ],
      ],
    ];
    if (count($drivers) == 1) {
      $form['database']['driver']['#disabled'] = TRUE;
    }

    // Add driver-specific configuration options.
    foreach ($drivers as $key => $driver) {
      $form['database']['driver']['#options'][$key] = $driver->name();

      $form['database']['settings'][$key] = $driver->getFormOptions($default_options);
      unset($form['database']['settings'][$key]['advanced_options']['prefix']['#description']);

      // This is a multi-step form and is not rebuilt during submission so
      // #limit_validation_errors is not used. The database and username fields
      // for mysql and pgsql must not be required.
      $form['database']['settings'][$key]['database']['#required'] = FALSE;
      $form['database']['settings'][$key]['username']['#required'] = FALSE;
      $form['database']['settings'][$key]['database']['#states'] = [
        'required' => [
          ':input[name=source_connection]' => ['value' => ''],
          ':input[name=driver]' => ['value' => $key],
        ],
      ];
      if (!str_ends_with($key, '\\sqlite')) {
        $form['database']['settings'][$key]['username']['#states'] = [
          'required' => [
            ':input[name=source_connection]' => ['value' => ''],
            ':input[name=driver]' => ['value' => $key],
          ],
        ];
        $form['database']['settings'][$key]['password']['#states'] = [
          'required' => [
            ':input[name=source_connection]' => ['value' => ''],
            ':input[name=driver]' => ['value' => $key],
          ],
        ];
      }

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
      '#title' => $this->t('Document root for files'),
      '#default_value' => Settings::get('migrate_file_public_path') ?? '',
      '#description' => $this->t('To import files from your current Drupal site, enter a local file directory containing your site (e.g. /var/www/docroot), or your site address (for example http://example.com).'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => ['value' => '6'],
        ],
      ],
      '#element_validate' => ['::validatePaths'],
    ];

    $form['source']['source_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document root for public files'),
      '#default_value' => Settings::get('migrate_file_public_path') ?? '',
      '#description' => $this->t('To import public files from your current Drupal site, enter a local file directory containing your site (e.g. /var/www/docroot), or your site address (for example http://example.com).'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => ['value' => '7'],
        ],
      ],
      '#element_validate' => ['::validatePaths'],
    ];

    $form['source']['source_private_file_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document root for private files'),
      '#default_value' => Settings::get('migrate_file_private_path') ?? '',
      '#description' => $this->t('To import private files from your current Drupal site, enter a local file directory containing your site (e.g. /var/www/docroot). Leave blank to use the same value as Public files directory.'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => ['value' => '7'],
        ],
      ],
      '#element_validate' => ['::validatePaths'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $source_connection = $form_state->getValue('source_connection');
    if ($source_connection) {
      $info = Database::getConnectionInfo($source_connection);
      $database = reset($info);
    }
    else {
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
      $connection = NULL;
      if ($errors = $drivers[$driver]->validateDatabaseSettings($database)) {
        foreach ($errors as $name => $message) {
          $this->errors[$name] = $message;
        }
      }
    }

    // Get the Drupal version of the source database so it can be validated.
    $error_key = $database['driver'] . '][database';
    if (!$this->errors) {
      try {
        $connection = $this->getConnection($database);
      }
      catch (\Exception $e) {
        $msg = $this->t('Failed to connect to your database server. The server reports the following message: %error.<ul><li>Is the database server running?</li><li>Does the database exist, and have you entered the correct database name?</li><li>Have you entered the correct username and password?</li><li>Have you entered the correct database hostname?</li></ul>', ['%error' => $e->getMessage()]);
        $this->errors[$error_key] = $msg;
      }
    }

    // Get the Drupal version of the source database so it can be validated.
    if (!$this->errors) {
      $version = (string) $this->getLegacyDrupalVersion($connection);
      if (!$version) {
        $this->errors[$error_key] = $this->t('Source database does not contain a recognizable Drupal version.');
      }
      elseif ($version !== (string) $form_state->getValue('version')) {
        $this->errors['version'] = $this->t('Source database is Drupal version @version but version @selected was selected.',
          [
            '@version' => $version,
            '@selected' => $form_state->getValue('version'),
          ]);
      }
    }

    // Setup migrations and save form data to private store.
    if (!$this->errors) {
      try {
        $this->setupMigrations($connection, $version, $database, $form_state);
      }
      catch (BadPluginDefinitionException $e) {
        // BadPluginDefinitionException occurs if the source_module is not
        // defined, which happens during testing.
        $this->errors[$error_key] = $e->getMessage();
      }
      catch (RequirementsException $e) {
        $this->errors[$error_key] = $e->getMessage();
      }
    }

    // Display all errors as a list of items.
    if ($this->errors) {
      $form_state->setError($form, $this->t('<h3>Resolve all issues below to continue the upgrade.</h3>'));
      foreach ($this->errors as $name => $message) {
        $form_state->setErrorByName($name, $message);
      }
    }
  }

  /**
   * The #element_validate handler for the source path elements.
   *
   * Ensures that entered path can be read.
   */
  public function validatePaths($element, FormStateInterface $form_state) {
    $version = $form_state->getValue('version');
    // Only validate the paths relevant to the legacy Drupal version.
    if (($version !== '7')
      && ($element['#name'] == 'source_base_path' || $element['#name'] == 'source_private_file_path')) {
      return;
    }

    if ($version !== '6' && ($element['#name'] == 'd6_source_base_path')) {
      return;
    }

    if ($source = $element['#value']) {
      $msg = $this->t('Failed to read from @title.', ['@title' => $element['#title']]);
      if (UrlHelper::isExternal($source)) {
        try {
          $this->httpClient->head($source);
        }
        catch (TransferException $e) {
          $msg .= ' ' . $this->t('The server reports the following message: %error.', ['%error' => $e->getMessage()]);
          $this->errors[$element['#name']] = $msg;
        }
      }
      elseif (!file_exists($source) || (!is_dir($source)) || (!is_readable($source))) {
        $this->errors[$element['#name']] = $msg;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('step', 'idconflict');
    $form_state->setRedirect('migrate_drupal_ui.upgrade_idconflict');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Review upgrade');
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
    $database_types = [];
    foreach (Database::getDriverList()->getInstallableList() as $name => $driver) {
      $database_types[$name] = $driver->getInstallTasks();
    }
    return $database_types;
  }

  /**
   * Gets and stores information for this migration in temporary store.
   *
   * Gets all the migrations, converts each to an array and stores it in the
   * form state. The source base path for public and private files is also
   * put into form state.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection used.
   * @param string $version
   *   The Drupal version.
   * @param array $database
   *   Database array representing the source Drupal database.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   *   Thrown when a lock for the backend storage could not be acquired.
   */
  protected function setupMigrations(Connection $connection, $version, array $database, FormStateInterface $form_state) {
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

    // Store information in the private store.
    $this->store->set('version', $version);
    $this->store->set('migrations', $migration_array);
    if ($version == 6) {
      $this->store->set('source_base_path', $form_state->getValue('d6_source_base_path'));
    }
    else {
      $this->store->set('source_base_path', $form_state->getValue('source_base_path'));
    }
    $this->store->set('source_private_file_path', $form_state->getValue('source_private_file_path'));
    // Store the retrieved system data in the private store.
    $this->store->set('system_data', $system_data);
  }

}
