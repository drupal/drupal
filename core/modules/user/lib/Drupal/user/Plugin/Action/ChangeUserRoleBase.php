<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Action\ChangeUserRoleBase.
 */

namespace Drupal\user\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;

/**
 * Provides a base class for operations to change a user's role.
 */
abstract class ChangeUserRoleBase extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'rid' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $roles = user_role_names(TRUE);
    unset($roles[DRUPAL_AUTHENTICATED_RID]);
    $form['rid'] = array(
      '#type' => 'radios',
      '#title' => t('Role'),
      '#options' => $roles,
      '#default_value' => $this->configuration['rid'],
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['rid'] = $form_state['values']['rid'];
  }

}
