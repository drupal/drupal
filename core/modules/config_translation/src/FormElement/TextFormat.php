<?php

/**
 * @file
 * Contains \Drupal\config_translation\FormElement\TextFormat.
 */

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the text_format element for the configuration translation interface.
 */
class TextFormat extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getSourceElement(LanguageInterface $source_language, $source_config) {
    // Instead of the formatted output show a disabled textarea. This allows for
    // easier side-by-side comparison, especially with formats with text
    // editors.
    return $this->getTranslationElement($source_language, $source_config, $source_config) + array(
      '#value' => $source_config['value'],
      '#disabled' => TRUE,
      '#allow_focus' => TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationElement(LanguageInterface $translation_language, $source_config, $translation_config) {
    return array(
      '#type' => 'text_format',
      // Override the #default_value property from the parent class.
      '#default_value' => $translation_config['value'],
      '#format' => $translation_config['format'],
      // @see \Drupal\config_translation\Element\FormElementBase::getTranslationElement()
      '#allowed_formats' => array($source_config['format']),
    ) + parent::getTranslationElement($translation_language, $source_config, $translation_config);
  }

}
