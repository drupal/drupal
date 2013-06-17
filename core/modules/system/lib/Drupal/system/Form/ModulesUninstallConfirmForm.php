<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesUninstallConfirmForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds a confirmation form to uninstall selected modules.
 *
 * Used internally from system_modules_uninstall().
 */
class ModulesUninstallConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Confirm uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/modules/uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Would you like to continue with uninstalling the above?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'system_modules_uninstall_confirm_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $modules
   *   The array of modules.
   */
  public function buildForm(array $form, array &$form_state, $modules = array(), Request $request = NULL) {
    $uninstall = array();
    // Construct the hidden form elements and list items.
    foreach ($modules as $module => $value) {
      $info = drupal_parse_info_file(drupal_get_path('module', $module) . '/' . $module . '.info.yml');
      $uninstall[] = $info['name'];
      $form['uninstall'][$module] = array('#type' => 'hidden', '#value' => 1);
    }

    $form['#confirmed'] = TRUE;
    $form['uninstall']['#tree'] = TRUE;
    $form['text'] = array('#markup' => '<p>' . t('The following modules will be completely uninstalled from your site, and <em>all data from these modules will be lost</em>!') . '</p>');
    $form['modules'] = array('#theme' => 'item_list', '#items' => $uninstall);

    return parent::buildForm($form, $form_state, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

}
