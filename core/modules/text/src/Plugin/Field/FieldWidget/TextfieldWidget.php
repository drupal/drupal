<?php

namespace Drupal\text\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\ElementInterface;
use Drupal\Core\Render\Element\Widget;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Element\TextFormat;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textfield' widget.
 */
#[FieldWidget(
  id: 'text_textfield',
  label: new TranslatableMarkup('Text field'),
  field_types: ['text'],
)]
class TextfieldWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function singleElementObject(FieldItemListInterface $items, $delta, Widget $widget, ElementInterface $form, FormStateInterface $form_state): ElementInterface {
    $widget = parent::singleElementObject($items, $delta, $widget, $form, $form_state);
    $allowed_formats = $this->getFieldSetting('allowed_formats');

    $widget = $widget->getChild('value');
    $type = $widget->type;
    $widget = $widget->changeType(TextFormat::class);
    $widget->format = $items[$delta]->format ?? NULL;
    $widget->base_type = $type;
    if ($allowed_formats && !$this->isDefaultValueWidget($form_state)) {
      $widget->allowed_formats = $allowed_formats;
    }

    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    if (isset($element['format']['#access']) && !$element['format']['#access'] && preg_match('/^[0-9]*\.format$/', $violation->getPropertyPath())) {
      // Ignore validation errors for formats that may not be changed,
      // such as when existing formats become invalid.
      // See \Drupal\filter\Element\TextFormat::processFormat().
      return FALSE;
    }
    return $element;
  }

}
