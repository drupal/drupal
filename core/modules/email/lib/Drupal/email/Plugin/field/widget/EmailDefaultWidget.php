<?php

/**
 * @file
 * Definition of Drupal\email\Plugin\field\widget\EmailDefaultWidget.
 */

namespace Drupal\email\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'email_default' widget.
 *
 * @Plugin(
 *   id = "email_default",
 *   module = "email",
 *   label = @Translation("E-mail"),
 *   field_types = {
 *     "email"
 *   },
 *   settings = {
 *     "placeholder" = ""
 *   }
 * )
 */
class EmailDefaultWidget extends WidgetBase {

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
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element['value'] = $element + array(
      '#type' => 'email',
      '#default_value' => isset($items[$delta]['value']) ? $items[$delta]['value'] : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
    );
    return $element;
  }

}
