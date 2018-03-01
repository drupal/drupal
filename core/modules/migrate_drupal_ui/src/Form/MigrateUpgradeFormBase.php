<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form base for the Migrate Upgrade UI.
 */
abstract class MigrateUpgradeFormBase extends FormBase {

  use MigrationConfigurationTrait;

  /**
   * Private temporary storage.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $store;

  /**
   * Constructs the Migrate Upgrade Form Base.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore_private
   *   Private store.
   */
  public function __construct(PrivateTempStoreFactory $tempstore_private) {
    $this->store = $tempstore_private->get('migrate_drupal_ui');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * Gets and stores information for this migration in temporary store.
   *
   * Gets all the migrations, converts each to an array and stores it in the
   * form state. The source base path for public and private files is also
   * put into form state.
   *
   * @param array $database
   *   Database array representing the source Drupal database.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setupMigrations(array $database, FormStateInterface $form_state) {
    $connection = $this->getConnection($database);
    $version = $this->getLegacyDrupalVersion($connection);
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

  /**
   * Helper to redirect to the Overview form.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  protected function restartUpgradeForm() {
    $this->store->set('step', 'overview');
    return $this->redirect('migrate_drupal_ui.upgrade');
  }

  /**
   * Returns a caption for the button that confirms the action.
   *
   * @return string
   *   The form confirmation text.
   */
  abstract protected function getConfirmText();

}
