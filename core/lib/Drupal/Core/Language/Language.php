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
    'direction' => self::DIRECTION_LTR,
    'weight' => 0,
    'locked' => FALSE,
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
   * Locked indicates a language used by the system, not an actual language.
   *
   * Examples of locked languages are, LANGCODE_NOT_SPECIFIED, und, and
   * LANGCODE_NOT_APPLICABLE, zxx, which are usually shown in language selects
   * but hidden in places like the Language configuration and cannot be deleted.
   *
   * @var bool
   */
  protected $locked = FALSE;

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
  public function getId() {
    return $this->id;
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
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return static::getDefaultLangcode() == $this->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * Sort language objects.
   *
   * @param \Drupal\Core\Language\LanguageInterface[] $languages
   *   The array of language objects keyed by langcode.
   */
  public static function sort(&$languages) {
    uasort($languages, function (LanguageInterface $a, LanguageInterface $b) {
      $a_weight = $a->getWeight();
      $b_weight = $b->getWeight();
      if ($a_weight == $b_weight) {
        return strnatcasecmp($a->getName(), $b->getName());
      }
      return ($a_weight < $b_weight) ? -1 : 1;
    });
  }

  /**
   * Gets the default langcode.
   *
   * @return string
   *   The current default langcode.
   */
  protected static function getDefaultLangcode() {
    $language = \Drupal::service('language.default')->get();
    return $language->getId();
  }

}
