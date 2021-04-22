<?php

namespace Drupal\form_test\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test vertical tabs.
 *
 * @internal
 */
class FormTestVerticalTabsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_vertical_tabs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tab_count = 3;

    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-tab' . $tab_count,
    ];

    for ($i = 1; $i <= $tab_count; $i++) {
      $form['tab' . $i] = [
        '#type' => 'fieldset',
        '#title' => t('Tab @num', ['@num' => $i]),
        '#group' => 'vertical_tabs',
        '#access' => \Drupal::currentUser()->hasPermission('access vertical_tab_test tabs'),
      ];
      $form['tab' . $i]['field' . $i] = [
        '#title' => t('Field @num', ['@num' => $i]),
        '#type' => 'textfield',

      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    // This won't have a proper JSON header, but Drupal doesn't check for that
    // anyway so this is fine until it's replaced with a JsonResponse.
    print Json::encode($form_state->getValues());
    exit;
  }

}
