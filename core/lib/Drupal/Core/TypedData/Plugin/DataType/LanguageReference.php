<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\LanguageReference.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\DataReferenceBase;

/**
 * Defines the 'language_reference' data type.
 *
 * This serves as 'language' property of language field items and gets
 * its value set from the parent, i.e. LanguageItem.
 *
 * The plain value is the language object, i.e. an instance of
 * \Drupal\Core\Language\Language. For setting the value the language object or
 * the language code as string may be passed.
 *
 * @DataType(
 *   id = "language_reference",
 *   label = @Translation("Language reference"),
 *   definition_class = "\Drupal\Core\TypedData\DataReferenceDefinition"
 * )
 */
class LanguageReference extends DataReferenceBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetIdentifier() {
    $language = $this->getTarget();
    return isset($language) ? $language->id() : NULL;
  }

}
