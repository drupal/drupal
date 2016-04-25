<?php

namespace Drupal\Core\Field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'Language' widget.
 *
 * @FieldWidget(
 *   id = "language_select",
 *   label = @Translation("Language select"),
 *   field_types = {
 *     "language"
 *   }
 * )
 */
class LanguageSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + array(
      '#type' => 'language_select',
      '#default_value' => $items[$delta]->value,
      '#languages' => LanguageInterface::STATE_ALL,
    );

    return $element;
  }

}
