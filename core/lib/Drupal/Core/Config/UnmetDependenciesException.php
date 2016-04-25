<?php

namespace Drupal\Core\Config;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * An exception thrown if configuration has unmet dependencies.
 */
class UnmetDependenciesException extends ConfigException {

  /**
   * A list of configuration objects that have unmet dependencies.
   *
   * @var array
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
   *   A list of configuration objects that have unmet dependencies.
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
   *
   * @return string
   */
  public function getTranslatedMessage(TranslationInterface $string_translation, $extension) {
    return $string_translation->formatPlural(
      count($this->getConfigObjects()),
      'Unable to install @extension, %config_names has unmet dependencies.',
      'Unable to install @extension, %config_names have unmet dependencies.',
      [
        '%config_names' => implode(', ', $this->getConfigObjects()),
        '@extension' => $extension,
      ]
    );
  }

  /**
   * Creates an exception for an extension and a list of configuration objects.
   *
   * @param $extension
   *   The name of the extension that is being installed.
   * @param array $config_objects
   *   A list of configuration object names that have unmet dependencies
   *
   * @return \Drupal\Core\Config\PreExistingConfigException
   */
  public static function create($extension, array $config_objects) {
    $message = SafeMarkup::format('Configuration objects (@config_names) provided by @extension have unmet dependencies',
      array(
        '@config_names' => implode(', ', $config_objects),
        '@extension' => $extension
      )
    );
    $e = new static($message);
    $e->configObjects = $config_objects;
    $e->extension = $extension;
    return $e;
  }

}
