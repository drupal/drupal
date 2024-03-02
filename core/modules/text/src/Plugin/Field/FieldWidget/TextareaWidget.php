<?php

namespace Drupal\text\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textarea' widget.
 */
#[FieldWidget(
  id: 'text_textarea',
  label: new TranslatableMarkup('Text area (multiple rows)'),
  field_types: ['text_long'],
)]
class TextareaWidget extends StringTextareaWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['rows']['#description'] = $this->t('Text editors may override this setting.');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $main_widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $allowed_formats = $this->getFieldSetting('allowed_formats');

    $element = $main_widget['value'];
    $element['#type'] = 'text_format';
    $element['#format'] = $items[$delta]->format;
    $element['#base_type'] = $main_widget['value']['#type'];

    if ($allowed_formats && !$this->isDefaultValueWidget($form_state)) {
      $element['#allowed_formats'] = $allowed_formats;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    if (isset($element['format']['#access']) && !$element['format']['#access'] && preg_match('/^[0-9]*\.format$/', $violation->getPropertyPath())) {
      // Ignore validation errors for formats if formats may not be changed,
      // such as when existing formats become invalid.
      // See \Drupal\filter\Element\TextFormat::processFormat().
      return FALSE;
    }
    return $element;
  }

}
