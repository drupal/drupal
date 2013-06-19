<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesInstallConfirmForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds a confirmation form for required modules.
 *
 * Used internally in system_modules().
 */
class ModulesInstallConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Some required modules must be enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Continue');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/modules';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Would you like to continue with the above?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'system_modules_confirm_form';
  }

  /**
   * {@inheritdoc}
   * @param array $modules
   *   The array of modules.
   * @param array $storage
   *   Temporary storage of module dependency information.
   */
  public function buildForm(array $form, array &$form_state, $modules = array(), $storage = array(), Request $request = NULL) {
    $items = array();

    $form['validation_modules'] = array('#type' => 'value', '#value' => $modules);
    $form['status']['#tree'] = TRUE;

    foreach ($storage['more_required'] as $info) {
      $t_argument = array(
        '@module' => $info['name'],
        '@required' => implode(', ', $info['requires']),
      );
      $items[] = format_plural(count($info['requires']), 'You must enable the @required module to install @module.', 'You must enable the @required modules to install @module.', $t_argument);
    }

    foreach ($storage['missing_modules'] as $name => $info) {
      $t_argument = array(
        '@module' => $name,
        '@depends' => implode(', ', $info['depends']),
      );
      $items[] = format_plural(count($info['depends']), 'The @module module is missing, so the following module will be disabled: @depends.', 'The @module module is missing, so the following modules will be disabled: @depends.', $t_argument);
    }

    $form['modules'] = array('#theme' => 'item_list', '#items' => $items);

    return parent::buildForm($form, $form_state, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

}
