<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\Language\LanguageInterface;
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

  /**
   * Returns all valid values for a `langcode` config value.
   *
   * @return string[]
   *   All possible valid langcodes. This includes all langcodes in the standard
   *   list of human languages, along with special langcodes like `und`, `zxx`,
   *   and `site_default`, which Drupal uses internally. If any custom languages
   *   are defined, they will be included as well.
   *
   * @see \Drupal\Core\Language\LanguageManagerInterface::getLanguages()
   * @see \Drupal\Core\Language\LanguageManagerInterface::getStandardLanguageList()
   */
  public static function getAllValidLangcodes(): array {
    $language_manager = \Drupal::languageManager();

    return array_unique([
      ...array_keys($language_manager::getStandardLanguageList()),
      // We can't use LanguageInterface::STATE_ALL because it will exclude the
      // site default language in certain situations.
      // @see \Drupal\Core\Language\LanguageManager::filterLanguages()
      ...array_keys($language_manager->getLanguages(LanguageInterface::STATE_LOCKED | LanguageInterface::STATE_CONFIGURABLE | LanguageInterface::STATE_SITE_DEFAULT)),
      // Include special language codes used internally.
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      LanguageInterface::LANGCODE_DEFAULT,
      LanguageInterface::LANGCODE_SYSTEM,
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
  }

}
