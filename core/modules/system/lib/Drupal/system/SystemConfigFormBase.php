<?php

/**
 * @file
 * Contains \Drupal\system\SystemConfigFormBase.
 */

namespace Drupal\system;

use Drupal\Core\Form\FormInterface;

/**
 * Base class for implementing system configuration forms.
 */
abstract class SystemConfigFormBase implements FormInterface {

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#button_type' => 'primary',
    );

    // By default, render the form using theme_system_settings_form().
    $form['#theme'] = 'system_settings_form';

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message(t('The configuration options have been saved.'));
  }

}
