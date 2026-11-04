<?php

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Event object to allow configuration to be overridden by modules.
 */
class ConfigModuleOverridesEvent extends Event {

  /**
   * Configuration overrides.
   *
   * @var array
   */
  protected $overrides;

  /**
   * Constructs a configuration overrides event object.
   *
   * @param array $names
   *   A list of configuration names.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   (optional) The language for this configuration.
   */
  public function __construct(
    protected array $names,
    protected ?LanguageInterface $language = NULL,
  ) {
    $this->overrides = [];
  }

  /**
   * Gets configuration names.
   *
   * @return array
   *   The list of configuration names that can be overridden.
   */
  public function getNames() {
    return $this->names;
  }

  /**
   * Gets configuration language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The configuration language object.
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * Get configuration overrides.
   *
   * @return array
   *   The array of configuration overrides.
   */
  public function getOverrides() {
    return $this->overrides;
  }

  /**
   * Sets a configuration override for the given name.
   *
   * @param string $name
   *   The configuration object name to override.
   * @param array $values
   *   The values in the configuration object to override.
   *
   * @return $this
   */
  public function setOverride($name, array $values) {
    if (in_array($name, $this->names)) {
      if (isset($this->overrides[$name])) {
        // Existing overrides take precedence since these will have been added
        // by events with a higher priority.
        $this->overrides[$name] = NestedArray::mergeDeepArray([$values, $this->overrides[$name]], TRUE);
      }
      else {
        $this->overrides[$name] = $values;
      }
    }
    return $this;
  }

}
