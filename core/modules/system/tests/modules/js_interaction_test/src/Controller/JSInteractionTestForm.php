<?php

declare(strict_types=1);

namespace Drupal\js_interaction_test\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Controller for testing fault tolerant JavaScript interactions.
 */
class JSInteractionTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return __CLASS__;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No-op.
  }

  /**
   * Creates the test form.
   *
   * The form provides:
   * - A link that is obstructed (blocked) by another element.
   * - A link that, when clicked, removes the blocking element after some time.
   * - A field that is disabled.
   * - A link that, when clicked, enables the field after some time.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return [
      'target_link' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('<current>'),
        '#title' => $this->t('Target link'),
      ],
      'blocker_element' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['blocker-element'],
        ],
      ],
      'remove_blocker_trigger' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('<current>'),
        '#title' => $this->t('Remove Blocker Trigger'),
        '#attributes' => [
          'class' => ['remove-blocker-trigger'],
        ],
      ],
      'target_field' => [
        '#type' => 'textfield',
        '#maxlength' => 20,
        '#disabled' => TRUE,
      ],
      'enable_field_trigger' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('<current>'),
        '#title' => $this->t('Enable Field Trigger'),
        '#attributes' => [
          'class' => ['enable-field-trigger'],
        ],
      ],
      '#attached' => [
        'library' => [
          'js_interaction_test/js_interaction_test',
        ],
      ],
    ];
  }

}
