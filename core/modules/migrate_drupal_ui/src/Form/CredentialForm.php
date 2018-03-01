<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate Upgrade database credential form.
 *
 * @internal
 */
class CredentialForm extends MigrateUpgradeFormBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * CredentialForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore_private
   *   The private tempstore factory.
   */
  public function __construct(RendererInterface $renderer, PrivateTempStoreFactory $tempstore_private) {
    parent::__construct($tempstore_private);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('tempstore.private')
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

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
        // Setup migrations and save form data to private store.
        $this->setupMigrations($database, $form_state);
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
    return drupal_get_database_types();
  }

}
