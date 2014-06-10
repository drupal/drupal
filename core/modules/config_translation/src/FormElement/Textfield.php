<?php

/**
 * @file
 * Contains \Drupal\config_translation\FormElement\Textfield.
 */

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the textfield element for the configuration translation interface.
 */
class Textfield implements ElementInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormElement(array $definition, LanguageInterface $language, $value) {
    return array(
      '#type' => 'textfield',
      '#default_value' => $value,
      '#title' => $this->t($definition['label']) . '<span class="visually-hidden"> (' . $language->name . ')</span>',
      '#attributes' => array('lang' => $language->id),
    );
  }

}
