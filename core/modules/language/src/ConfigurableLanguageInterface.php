<?php

/**
 * @file
 * Contains \Drupal\language\ConfigurableLanguageInterface.
 */

namespace Drupal\language;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides an interface defining a language entity.
 */
interface ConfigurableLanguageInterface extends ConfigEntityInterface, LanguageInterface {

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
