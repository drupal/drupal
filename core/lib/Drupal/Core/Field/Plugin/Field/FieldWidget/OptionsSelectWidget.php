<?php

namespace Drupal\Core\Field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'options_select' widget.
 */
#[FieldWidget(
  id: 'options_select',
  label: new TranslatableMarkup('Select list'),
  field_types: [
    'entity_reference',
    'list_integer',
    'list_float',
    'list_string',
  ],
  multiple_values: TRUE,
)]
class OptionsSelectWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);
    // Do not display a 'multiple' select box if there is only one option.
    $multiple = $this->multiple && count($options) > 1;

    // If the selected option is empty and the field is required, add an option
    // to force the user to choose.
    if (!isset($options['_none']) && empty($selected) && $this->required && !$multiple) {
      // Ensure there is an empty option if the widget needs one.
      $empty_label = $this->t('- Select a value -');
      $this->sanitizeLabel($empty_label);
      $options = ['_none' => $empty_label] + $options;
    }

    $element += [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $selected,
      '#multiple' => $multiple,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    // Add a 'none' option for non-required fields.
    if (!$this->required) {
      return $this->t('- None -');
    }
    return NULL;
  }

}
