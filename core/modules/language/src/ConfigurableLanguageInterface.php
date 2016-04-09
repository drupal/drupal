<?php

namespace Drupal\language;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides an interface defining a language entity.
 */
interface ConfigurableLanguageInterface extends ConfigEntityInterface, LanguageInterface {

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
   * Sets the weight of the language.
   *
   * @param int $weight
   *   The weight, used to order languages with larger positive weights sinking
   *   items toward the bottom of lists.
   *
   * @return $this
   */
  public function setWeight($weight);

}
