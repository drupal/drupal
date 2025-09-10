<?php

declare(strict_types=1);

namespace Drupal\test_htmx\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;

/**
 * A small form used to test HTMX dynamic forms.
 */
class HtmxTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'htmx_form_builder_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $type = NULL, ?string $selected = NULL) {
    $type = $type ?? '';
    $selected = $selected ?? '';
    $formUrl = Url::fromRoute('<current>');
    $form['type'] = [
      '#type' => 'select',
      '#title' => 'Type',
      '#empty_value' => '',
      '#options' => [
        'a' => 'A',
        'b' => 'B',
      ],
      '#default_value' => $type,
    ];
    (new Htmx())
      ->post($formUrl)
      ->swap('none')
      ->swapOob('true')
      ->applyTo($form['type']);
    $defaultType = $form_state->getValue('type', $type);
    $form['selected'] = [
      '#type' => 'select',
      '#title' => 'Selected',
      '#options' => $this->buildDependentOptions($defaultType),
      '#empty_value' => '',
      '#default_value' => $selected,
    ];
    (new Htmx())
      ->post($formUrl)
      ->swap('none')
      ->swapOob('true')
      ->applyTo($form['selected']);
    $form['data'] = [
      '#title' => 'Values',
      '#type' => 'item',
      '#markup' => '',
    ];
    (new Htmx())
      ->swapOob(TRUE)
      ->applyTo($form['data'], '#wrapper_attributes');

    $push = FALSE;
    if ($this->getTriggerElement($form_state) === 'type') {
      $form['data']['#markup'] = '';
      $push = Url::fromRoute('test_htmx.form_builder_test');
    }
    elseif ($this->getTriggerElement($form_state) === 'selected') {
      // A value is selected.
      $defaultSelection = $form_state->getValue('selected', $selected);
      // Also update the browser URL.
      $push = Url::fromRoute('test_htmx.form_builder_test', ['type' => $defaultType, 'selected' => $defaultSelection]);
      if ($defaultType && $defaultSelection) {
        $form['data']['#markup'] = "Data is $defaultType:$defaultSelection";
      }
    }
    elseif ($type && $selected) {
      $form['data']['#markup'] = "Data is $type:$selected";
    }
    if ($push) {
      $htmxPost = (new Htmx())
        ->post($push)
        ->pushUrlHeader($push);
      $htmxPost->applyTo($form['type']);
      $htmxPost->applyTo($form['selected']);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

  protected function buildDependentOptions(string $selected): array {
    $options = [
      'a' => [
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
      ],
      'b' => [
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
      ],
    ];
    return $options[$selected] ?? [];
  }

  protected function getTriggerElement($form_state): string|bool {
    $input = $form_state->getUserInput();
    return !empty($input['_triggering_element_name']) ? $input['_triggering_element_name'] : FALSE;
  }

}
