<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure maintenance settings for this site.
 *
 * @internal
 */
class SiteMaintenanceModeForm extends ConfigFormBase {

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
  * The permission handler.
  *
  * @var \Drupal\user\PermissionHandlerInterface
  */
  protected $permissionHandler;

  /**
   * Constructs a new SiteMaintenanceModeForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection to use.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, PermissionHandlerInterface $permission_handler) {
    parent::__construct($config_factory);
    $this->state = $state;
    $this->permissionHandler = $permission_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('user.permissions')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_site_maintenance_mode';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.maintenance'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('system.maintenance');
    $permissions = $this->permissionHandler->getPermissions();
    $permission_label = $permissions['access site in maintenance mode']['title'];
    $form['maintenance_mode'] = [
      '#type' => 'checkbox',
      '#title' => t('Put site into maintenance mode'),
      '#default_value' => $this->state->get('system.maintenance_mode'),
      '#description' => t('Visitors will only see the maintenance mode message. Only users with the "@permission-label" <a href=":permissions-url">permission</a> will be able to access the site. Authorized users can log in directly via the <a href=":user-login">user login</a> page.', ['@permission-label' => $permission_label, ':permissions-url' => $this->url('user.admin_permissions'), ':user-login' => $this->url('user.login')]),
    ];
    $form['maintenance_mode_message'] = [
      '#type' => 'textarea',
      '#title' => t('Message to display when in maintenance mode'),
      '#default_value' => $config->get('message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.maintenance')
      ->set('message', $form_state->getValue('maintenance_mode_message'))
      ->save();

    $this->state->set('system.maintenance_mode', $form_state->getValue('maintenance_mode'));
    parent::submitForm($form, $form_state);
  }

}
