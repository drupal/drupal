<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate Upgrade Incremental form.
 *
 * @internal
 */
class IncrementalForm extends MigrateUpgradeFormBase {

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
   * IncrementalForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore_private
   *   The private temp store factory.
   */
  public function __construct(StateInterface $state, DateFormatterInterface $date_formatter, PrivateTempStoreFactory $tempstore_private) {
    parent::__construct($tempstore_private);
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_drupal_ui_incremental_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all the data needed for this form.
    $date_performed = $this->state->get('migrate_drupal_ui.performed');

    // If data is missing or this is the wrong step, start over.
    if (!$date_performed || $this->store->get('step') != 'incremental') {
      return $this->restartUpgradeForm();
    }

    $form = parent::buildForm($form, $form_state);
    $form['#title'] = $this->t('Upgrade');

    // @todo Add back support for rollbacks.
    //   https://www.drupal.org/node/2687849
    $form['upgrade_option_item'] = [
      '#type' => 'item',
      '#prefix' => $this->t('An upgrade has already been performed on this site. To perform a new migration, create a clean and empty new install of Drupal 8. Rollbacks are not yet supported through the user interface. For more information, see the <a href=":url">upgrading handbook</a>.', [':url' => 'https://www.drupal.org/upgrade/migrate']),
      '#description' => $this->t('Last upgrade: @date', ['@date' => $this->dateFormatter->format($date_performed)]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the database driver from state.
    $database_state_key = $this->state->get('migrate.fallback_state_key', '');
    if ($database_state_key) {
      try {
        $database = $this->state->get($database_state_key, [])['database'];
        if ($connection = $this->getConnection($database)) {
          if ($version = $this->getLegacyDrupalVersion($connection)) {
            $this->setupMigrations($database, $form_state);
            $valid_legacy_database = TRUE;
          }
        }
      }
      catch (DatabaseExceptionWrapper $exception) {
        // Hide DB exceptions and forward to the DB credentials form. In that
        // form we can more properly display errors and accept new credentials.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('step', 'credential');
    $form_state->setRedirect('migrate_drupal_ui.upgrade_credential');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Import new configuration and content from old site');
  }

}
