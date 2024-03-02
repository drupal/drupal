<?php

namespace Drupal\Core\Field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Language' widget.
 */
#[FieldWidget(
  id: 'language_select',
  label: new TranslatableMarkup('Language select'),
  field_types: ['language'],
)]
class LanguageSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'language_select',
      '#default_value' => $items[$delta]->value,
      '#languages' => $this->getSetting('include_locked') ? LanguageInterface::STATE_ALL : LanguageInterface::STATE_CONFIGURABLE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['include_locked'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['include_locked'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include locked languages such as <em>Not specified</em> and <em>Not applicable</em>'),
      '#default_value' => $this->getSetting('include_locked'),
    ];

    return $element;
  }

}
