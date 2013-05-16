<?php

/**
 * @file
 * Contains \Drupal\system\Form\SiteMaintenanceModeForm.
 */

namespace Drupal\system\Form;

use Drupal\system\SystemConfigFormBase;

/**
 * Configure maintenance settings for this site.
 */
class SiteMaintenanceModeForm extends SystemConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
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
      '#default_value' => $config->get('enabled'),
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
      ->set('enabled', $form_state['values']['maintenance_mode'])
      ->set('message', $form_state['values']['maintenance_mode_message'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
