<?php

namespace Drupal\Core\Field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;

/**
 * Plugin implementation of the 'email_default' widget.
 *
 * @FieldWidget(
 *   id = "email_default",
 *   label = @Translation("Email"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class EmailDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Textfield size'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = $this->t('No placeholder');
    }
    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'email',
      '#default_value' => $items[$delta]->value ?? NULL,
      '#placeholder' => $this->getSetting('placeholder'),
      '#size' => $this->getSetting('size'),
      '#maxlength' => Email::EMAIL_MAX_LENGTH,
    ];
    return $element;
  }

}
