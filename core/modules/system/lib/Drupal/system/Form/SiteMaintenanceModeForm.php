<?php

/**
 * @file
 * Contains \Drupal\system\Form\SiteMaintenanceModeForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\StateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure maintenance settings for this site.
 */
class SiteMaintenanceModeForm extends ConfigFormBase {

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * Constructs a new SiteMaintenanceModeForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state keyvalue collection to use.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    parent::__construct($config_factory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
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
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('system.maintenance');
    $form['maintenance_mode'] = array(
      '#type' => 'checkbox',
      '#title' => t('Put site into maintenance mode'),
      '#default_value' => $this->state->get('system.maintenance_mode'),
      '#description' => t('Visitors will only see the maintenance mode message. Only users with the "Access site in maintenance mode" <a href="@permissions-url">permission</a> will be able to access the site. Authorized users can log in directly via the <a href="@user-login">user login</a> page.', array('@permissions-url' => url('admin/config/people/permissions'), '@user-login' => url('user'))),
    );
    $form['maintenance_mode_message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message to display when in maintenance mode'),
      '#default_value' => $config->get('message'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('system.maintenance')
      ->set('message', $form_state['values']['maintenance_mode_message'])
      ->save();

    $this->state->set('system.maintenance_mode', $form_state['values']['maintenance_mode']);
    parent::submitForm($form, $form_state);
  }

}
