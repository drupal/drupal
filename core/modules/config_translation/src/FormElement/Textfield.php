<?php

/**
 * @file
 * Contains \Drupal\config_translation\FormElement\Textfield.
 */

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the textfield element for the configuration translation interface.
 */
class Textfield extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getTranslationElement(LanguageInterface $translation_language, $source_config, $translation_config) {
    return array(
      '#type' => 'textfield',
    ) + parent::getTranslationElement($translation_language, $source_config, $translation_config);
  }

}
