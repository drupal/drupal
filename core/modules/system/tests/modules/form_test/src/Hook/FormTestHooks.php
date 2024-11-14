<?php

declare(strict_types=1);

namespace Drupal\form_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for form_test.
 */
class FormTestHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_form_test_alter_form_alter', module: 'block')]
  public function blockFormFormTestAlterFormAlter(&$form, FormStateInterface $form_state) : void {
    \Drupal::messenger()->addStatus('block_form_form_test_alter_form_alter() executed.');
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    if ($form_id == 'form_test_alter_form') {
      \Drupal::messenger()->addStatus('form_test_form_alter() executed.');
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_form_test_alter_form_alter')]
  public function formFormTestAlterFormAlter(&$form, FormStateInterface $form_state) : void {
    \Drupal::messenger()->addStatus('form_test_form_form_test_alter_form_alter() executed.');
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_form_test_alter_form_alter', module: 'system')]
  public function systemFormFormTestAlterFormAlter(&$form, FormStateInterface $form_state) : void {
    \Drupal::messenger()->addStatus('system_form_form_test_alter_form_alter() executed.');
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the registration form.
   */
  #[Hook('form_user_register_form_alter')]
  public function formUserRegisterFormAlter(&$form, FormStateInterface $form_state) : void {
    $form['test_rebuild'] = [
      '#type' => 'submit',
      '#value' => t('Rebuild'),
      '#submit' => [
        'form_test_user_register_form_rebuild',
      ],
    ];
  }

  /**
   * Implements hook_form_FORM_ID_alter() for form_test_vertical_tabs_access_form().
   */
  #[Hook('form_form_test_vertical_tabs_access_form_alter')]
  public function formFormTestVerticalTabsAccessFormAlter(&$form, &$form_state, $form_id) : void {
    $form['vertical_tabs1']['#access'] = FALSE;
    $form['vertical_tabs2']['#access'] = FALSE;
    $form['tabs3']['#access'] = TRUE;
    $form['fieldset1']['#access'] = FALSE;
    $form['container']['#access'] = FALSE;
  }

}
