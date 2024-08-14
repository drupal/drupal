<?php

namespace Drupal\Core\Config;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * An exception thrown if configuration has unmet dependencies.
 */
class UnmetDependenciesException extends ConfigException {

  /**
   * A list of configuration objects that have unmet dependencies.
   *
   * @var array
   * The list is keyed by the config object name, and the value is an array of
   * the missing dependencies:
   * @code
   *
   * self::configObjects = [
   *   config_object_name => [
   *     'missing_dependency_1',
   *     'missing_dependency_2',
   *   ]
   * ];
   *
   * @endcode
   */
  protected $configObjects = [];

  /**
   * The name of the extension that is being installed.
   *
   * @var string
   */
  protected $extension;

  /**
   * Gets the list of configuration objects that have unmet dependencies.
   *
   * @return array
   *   A list of configuration objects that have unmet dependencies, keyed by
   *   object name, with the value being a list of the unmet dependencies.
   */
  public function getConfigObjects() {
    return $this->configObjects;
  }

  /**
   * Gets the name of the extension that is being installed.
   *
   * @return string
   *   The name of the extension that is being installed.
   */
  public function getExtension() {
    return $this->extension;
  }

  /**
   * Gets a translated message from the exception.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param string $extension
   *   The name of the extension that is being installed.
   *
   * @return string
   */
  public function getTranslatedMessage(TranslationInterface $string_translation, $extension) {
    return $string_translation->translate(
      'Unable to install %extension due to unmet dependencies: %config_names',
      [
        '%config_names' => static::formatConfigObjectList($this->configObjects),
        '%extension' => $extension,
      ]
    );
  }

  /**
   * Creates an exception for an extension and a list of configuration objects.
   *
   * @param $extension
   *   The name of the extension that is being installed.
   * @param array $config_objects
   *   A list of configuration keyed by configuration name, with unmet
   *   dependencies as the value.
   *
   * @return \Drupal\Core\Config\PreExistingConfigException
   */
  public static function create($extension, array $config_objects) {
    $message = new FormattableMarkup('Configuration objects provided by %extension have unmet dependencies: %config_names',
      [
        '%config_names' => static::formatConfigObjectList($config_objects),
        '%extension' => $extension,
      ]
    );
    $e = new static($message);
    $e->configObjects = $config_objects;
    $e->extension = $extension;
    return $e;
  }

  /**
   * Formats a list of configuration objects.
   *
   * @param array $config_objects
   *   A list of configuration object names that have unmet dependencies.
   *
   * @return string
   *   The imploded config_objects, formatted in an easy to read string.
   */
  protected static function formatConfigObjectList(array $config_objects) {
    $list = [];
    foreach ($config_objects as $config_object => $missing_dependencies) {
      $list[] = $config_object . ' (' . implode(', ', $missing_dependencies) . ')';
    }
    return implode(', ', $list);
  }

}
