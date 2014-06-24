<?php

/**
 * @file
 * Contains \Drupal\config_translation\FormElement\ElementInterface.
 */

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Provides an interface for configuration translation form elements.
 */
interface ElementInterface {

  /**
   * Returns the translation form element for a given configuration definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   Configuration schema type definition of the element.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Language object to display the translation form for.
   * @param string $value
   *   Default value for the form element.
   *
   * @return array
   *   Form API array to represent the form element.
   */
  public function getFormElement(DataDefinitionInterface $definition, LanguageInterface $language, $value);


}
