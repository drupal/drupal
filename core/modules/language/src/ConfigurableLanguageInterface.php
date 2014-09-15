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

}
