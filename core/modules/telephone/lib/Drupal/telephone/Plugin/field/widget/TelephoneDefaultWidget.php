<?php

/**
 * @file
 * Contains \Drupal\telephone\Plugin\field\widget\TelephoneDefaultWidget.
 */

namespace Drupal\telephone\Plugin\field\widget;

use Drupal\field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'telephone_default' widget.
 *
 * @FieldWidget(
 *   id = "telephone_default",
 *   label = @Translation("Telephone number"),
 *   field_types = {
 *     "telephone"
 *   },
 *   settings = {
 *     "placeholder" = ""
 *   }
 * )
 */
class TelephoneDefaultWidget extends WidgetBase {


  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(FieldInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element['value'] = $element + array(
      '#type' => 'tel',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
    );
    return $element;
  }

}
