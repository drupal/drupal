<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LanguageManager.
 */

namespace Drupal\Core\Language;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class responsible for initializing each language type.
 */
class LanguageManager {

  /**
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * An array of language objects keyed by language type.
   *
   * @var array
   */
  protected $languages;

  /**
   * Whether or not the language manager has been initialized.
   *
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * Whether already in the process of language initialization.
   *
   * @todo This is only needed due to the circular dependency between language
   *   and config. See http://drupal.org/node/1862202 for the plan to fix this.
   *
   * @var bool
   */
  protected $initializing = FALSE;

  /**
   * Initializes each language type to a language object.
   */
  public function init() {
    if ($this->initialized) {
      return;
    }
    if ($this->isMultilingual()) {
      foreach ($this->getLanguageTypes() as $type) {
        $this->getLanguage($type);
      }
    }
    $this->initialized = TRUE;
  }

  /**
   * Sets the $request property and resets all language types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
    $this->reset();
    $this->init();
  }

  /**
   * Returns a language object for the given type.
   *
   * @param string $type
   *   The language type, e.g. Language::TYPE_INTERFACE.
   *
   * @return \Drupal\Core\Language\Language
   *   A language object for the given type.
   */
  public function getLanguage($type) {
    if (isset($this->languages[$type])) {
      return $this->languages[$type];
    }

    if ($this->isMultilingual() && $this->request) {
      if (!$this->initializing) {
        $this->initializing = TRUE;
        // @todo Objectify the language system so that we don't have to load an
        //   include file and call out to procedural code. See
        //   http://drupal.org/node/1862202
        include_once DRUPAL_ROOT . '/core/includes/language.inc';
        $this->languages[$type] = language_types_initialize($type, $this->request);
        $this->initializing = FALSE;
      }
      else {
        // Config has called getLanguage() during initialization of a language
        // type. Simply return the default language without setting it on the
        // $this->languages property. See the TODO in the docblock for the
        // $initializing property.
        return $this->getLanguageDefault();
      }
    }
    else {
      $this->languages[$type] = $this->getLanguageDefault();
    }
    return $this->languages[$type];
  }

  /**
   * Resets the given language type or all types if none specified.
   *
   * @param string|null $type
   *   (optional) The language type to reset as a string, e.g.,
   *   Language::TYPE_INTERFACE, or NULL to reset all language types. Defaults
   *   to NULL.
   */
  public function reset($type = NULL) {
    if (!isset($type)) {
      $this->languages = array();
      $this->initialized = FALSE;
    }
    elseif (isset($this->languages[$type])) {
      unset($this->languages[$type]);
    }
  }

  /**
   * Returns whether or not the site has more than one language enabled.
   *
   * @return bool
   *   TRUE if more than one language is enabled, FALSE otherwise.
   */
  protected function isMultilingual() {
    return variable_get('language_count', 1) > 1;
  }

  /**
   * Returns an array of the available language types.
   *
   * @return array()
   *   An array of all language types.
   */
  protected function getLanguageTypes() {
    return array_keys(variable_get('language_types', language_types_get_default()));
  }

  /**
   * Returns a language object representing the site's default language.
   *
   * @return Drupal\Core\Language\Language
   *   A language object.
   */
  protected function getLanguageDefault() {
    $default_info = variable_get('language_default', array(
      'langcode' => 'en',
      'name' => 'English',
      'direction' => 0,
      'weight' => 0,
      'locked' => 0,
    ));
    return new Language($default_info + array('default' => TRUE));
  }

}
