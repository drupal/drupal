<?php

namespace Drupal\Core\Field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\ElementInterface;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Render\Element\Widget;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'string_textfield' widget.
 */
#[FieldWidget(
  id: 'string_textfield',
  label: new TranslatableMarkup('Textfield'),
  field_types: ['string'],
)]
class StringTextfieldWidget extends WidgetBase {

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
      '#title' => $this->t('Size of textfield'),
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

    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function singleElementObject(FieldItemListInterface $items, $delta, Widget $widget, ElementInterface $form, FormStateInterface $form_state): ElementInterface {
    $value = $widget->createChild('value', Textfield::class, copyProperties: TRUE);
    $value->default_value = $items[$delta]->value ?? NULL;
    $value->size = $this->getSetting('size');
    $value->placeholder = $this->getSetting('placeholder');
    $value->maxlength = $this->getFieldSetting('max_length');
    $value->attributes = ['class' => ['js-text-full', 'text-full']];
    return $widget;
  }

}
