<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LanguageDefault.
 */

namespace Drupal\Core\Language;

/**
 * Provides a simple get and set wrapper to the default language object.
 *
 * The default language must be provided without dependencies since it is both
 * configured and a dependency of the configuration system. The LanguageDefault
 * object is a container service. The default values are stored on the container
 * by \Drupal\Core\DrupalKernel::buildContainer(). This allows services to
 * override this parameter in a ServiceProvider, for example,
 * \Drupal\language\LanguageServiceProvider::alter().
 */
class LanguageDefault {

  /**
   * The default language.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $language;

  /**
   * Constructs the default language object.
   *
   * @param array $values
   *   The properties used to construct the default language.
   */
  public function __construct(array $values) {
    $this->set(new Language($values));
  }

  /**
   * Gets the default language.
   *
   * @return \Drupal\Core\Language\Language
   *   The default language.
   */
  public function get() {
    return $this->language;
  }

  /**
   * Sets the default language.
   *
   * @param \Drupal\Core\Language\Language $language
   *   The default language.
   */
  public function set(Language $language) {
    $language->default = TRUE;
    $this->language = $language;
  }

}
