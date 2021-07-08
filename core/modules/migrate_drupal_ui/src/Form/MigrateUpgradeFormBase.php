<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
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
   * The destination site major version.
   *
   * @var string
   */
  protected $destinationSiteVersion;

  /**
   * Constructs the Migrate Upgrade Form Base.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore_private
   *   The private tempstore factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MigrationPluginManagerInterface $migration_plugin_manager, StateInterface $state, PrivateTempStoreFactory $tempstore_private) {
    $this->configFactory = $config_factory;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->state = $state;
    $this->store = $tempstore_private->get('migrate_drupal_ui');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.migration'),
      $container->get('state'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the current major version.
    [$this->destinationSiteVersion] = explode('.', \Drupal::VERSION, 2);
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
   * Helper to redirect to the Overview form.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   *   Thrown when a lock for the backend storage could not be acquired.
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
