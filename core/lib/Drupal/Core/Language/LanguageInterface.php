<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LanguageInterface.
 */

namespace Drupal\Core\Language;

/**
 * Defines a language.
 */
interface LanguageInterface {

  /**
   * Special system language code (only applicable to UI language).
   *
   * Refers to the language used in Drupal and module/theme source code. Drupal
   * uses the built-in text for English by default, but if configured to allow
   * translation/customization of English, we need to differentiate between the
   * built-in language and the English translation.
   */
  const LANGCODE_SYSTEM = 'system';

  /**
   * The language code used when no language is explicitly assigned (yet).
   *
   * Should be used when language information is not available or cannot be
   * determined. This special language code is useful when we know the data
   * might have linguistic information, but we don't know the language.
   *
   * See http://www.w3.org/International/questions/qa-no-language#undetermined.
   */
  const LANGCODE_NOT_SPECIFIED = 'und';

  /**
   * The language code used when the marked object has no linguistic content.
   *
   * Should be used when we explicitly know that the data referred has no
   * linguistic content.
   *
   * See http://www.w3.org/International/questions/qa-no-language#nonlinguistic.
   */
  const LANGCODE_NOT_APPLICABLE = 'zxx';

  /**
   * Language code referring to the default language of data, e.g. of an entity.
   *
   * See the BCP 47 syntax for defining private language tags:
   * http://www.rfc-editor.org/rfc/bcp/bcp47.txt
   */
  const LANGCODE_DEFAULT = 'x-default';

  /**
   * The language state when referring to configurable languages.
   */
  const STATE_CONFIGURABLE = 1;

  /**
   * The language state when referring to locked languages.
   */
  const STATE_LOCKED = 2;

  /**
   * The language state used when referring to all languages.
   */
  const STATE_ALL = 3;

  /**
   * The language state used when referring to the site's default language.
   */
  const STATE_SITE_DEFAULT = 4;

  /**
   * The type of language used to define the content language.
   */
  const TYPE_CONTENT = 'language_content';

  /**
   * The type of language used to select the user interface.
   */
  const TYPE_INTERFACE = 'language_interface';

  /**
   * The type of language used for URLs.
   */
  const TYPE_URL = 'language_url';

  /**
   * Language written left to right. Possible value of $language->direction.
   */
  const DIRECTION_LTR = 0;

  /**
   * Language written right to left. Possible value of $language->direction.
   */
  const DIRECTION_RTL = 1;

  /**
   * Gets the name of the language.
   *
   * @return string
   *   The human-readable English name of the language.
   */
  public function getName();

  /**
   * Sets the name of the language.
   *
   * @param string $name
   *   The human-readable English name of the language.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the ID (language code).
   *
   * @return string
   *   The language code.
   */
  public function getId();

  /**
   * Sets the ID (language code).
   *
   * @param string $id
   *   The language code.
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Gets the text direction (left-to-right or right-to-left).
   *
   * @return int
   *   Either self::DIRECTION_LTR or self::DIRECTION_RTL.
   */
  public function getDirection();

  /**
   * Sets the direction of the language.
   *
   * @param int $direction
   *   Either self::DIRECTION_LTR or self::DIRECTION_RTL.
   *
   * @return $this
   */
  public function setDirection($direction);

  /**
   * Gets the weight of the language.
   *
   * @return int
   *   The weight, used to order languages with larger positive weights sinking
   *   items toward the bottom of lists.
   */
  public function getWeight();

  /**
   * Sets the weight of the language.
   *
   * @param int $weight
   *   The weight, used to order languages with larger positive weights sinking
   *   items toward the bottom of lists.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Returns whether this language is the default language.
   *
   * @return bool
   *   Whether the language is the default language.
   */
  public function isDefault();

  /**
   * Sets whether this language is the default language.
   *
   * @param bool $default
   *   TRUE if it is the default language.
   *
   * @return $this
   */
  public function setDefault($default);

  /**
   * Gets the language negotiation method ID for this language.
   *
   * @return string
   *   The method ID, for example
   *   \Drupal\language\LanguageNegotiatorInterface::METHOD_ID.
   */
  public function getNegotiationMethodId();

  /**
   * Sets the language negotiation method ID for this language.
   *
   * @param string $method_id
   *   The method ID, for example
   *   \Drupal\language\LanguageNegotiatorInterface::METHOD_ID.
   *
   * @return $this
   */
  public function setNegotiationMethodId($method_id);

}
