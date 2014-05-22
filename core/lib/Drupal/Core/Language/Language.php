<?php

/**
 * @file
 * Definition of Drupal\Core\Language\Language.
 */

namespace Drupal\Core\Language;

/**
 * An object containing the information for an interface language.
 *
 * @see language_default()
 */
class Language implements LanguageInterface {

  /**
   * The values to use to instantiate the default language.
   *
   * @var array
   */
  public static $defaultValues = array(
    'id' => 'en',
    'name' => 'English',
    'direction' => 0,
    'weight' => 0,
    'locked' => 0,
    'default' => TRUE,
  );

  // Properties within the Language are set up as the default language.

  /**
   * The human readable English name.
   *
   * @var string
   */
  public $name = '';

  /**
   * The ID, langcode.
   *
   * @var string
   */
  public $id = '';

  /**
   * The direction, left-to-right, or right-to-left.
   *
   * Defined using constants, either self::DIRECTION_LTR or self::DIRECTION_RTL.
   *
   * @var int
   */
  public $direction = self::DIRECTION_LTR;

  /**
   * The weight, used for ordering languages in lists, like selects or tables.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Flag indicating if this is the only site default language.
   *
   * @var bool
   */
  public $default = FALSE;

  /**
   * The language negotiation method used when a language was detected.
   *
   * The method ID, for example
   * \Drupal\language\LanguageNegotiatorInterface::METHOD_ID.
   *
   * @var string
   */
  public $method_id;

  /**
   * Locked indicates a language used by the system, not an actual language.
   *
   * Examples of locked languages are, LANGCODE_NOT_SPECIFIED, und, and
   * LANGCODE_NOT_APPLICABLE, zxx, which are usually shown in language selects
   * but hidden in places like the Language configuration and cannot be deleted.
   *
   * @var bool
   */
  public $locked = FALSE;

  /**
   * Constructs a new class instance.
   *
   * @param array $values
   *   An array of property values, keyed by property name, used to construct
   *   the language.
   */
  public function __construct(array $values = array()) {
    // Set all the provided properties for the language.
    foreach ($values as $key => $value) {
      $this->{$key} = $value;
    }
    // If some values were not set, set sane defaults of a predefined language.
    if (!isset($values['name']) || !isset($values['direction'])) {
      $predefined = LanguageManager::getStandardLanguageList();
      if (isset($predefined[$this->id])) {
        if (!isset($values['name'])) {
          $this->name = $predefined[$this->id][0];
        }
        if (!isset($values['direction']) && isset($predefined[$this->id][2])) {
          $this->direction = $predefined[$this->id][2];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirection() {
    return $this->direction;
  }

  /**
   * {@inheritdoc}
   */
  public function setDirection($direction) {
    $this->direction = $direction;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return $this->default;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($default) {
    $this->default = $default;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNegotiationMethodId() {
    return $this->method_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setNegotiationMethodId($method_id) {
    $this->method_id = $method_id;

    return $this;
  }

  /**
   * Sort language objects.
   *
   * @param array $languages
   *   The array of language objects keyed by langcode.
   */
  public static function sort(&$languages) {
    uasort($languages, 'Drupal\Component\Utility\SortArray::sortByWeightAndTitleKey');
  }

}
