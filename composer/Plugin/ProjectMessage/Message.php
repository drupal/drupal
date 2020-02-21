<?php

namespace Drupal\Composer\Plugin\ProjectMessage;

use Composer\Package\RootPackageInterface;

/**
 * Determine configuration.
 *
 * @internal
 */
class Message {

  /**
   * The root package.
   *
   * @var \Composer\Package\RootPackageInterface
   */
  protected $rootPackage;

  /**
   * The name of the event.
   *
   * @var string
   */
  protected $eventName;

  /**
   * The message to display.
   *
   * @var string[]
   */
  protected $messageText = [];

  /**
   * Construct a Config object.
   *
   * @param \Composer\Package\RootPackageInterface $root_package
   *   Composer package object for the root package.
   * @param string $event_name
   *   The event name.
   */
  public function __construct(RootPackageInterface $root_package, $event_name) {
    $this->rootPackage = $root_package;
    $this->eventName = $event_name;
  }

  public function getText() {
    if ($this->messageText) {
      return $this->messageText;
    }
    $package_config = $this->rootPackage->getExtra();
    $file = $this->eventName . '-message.txt';
    if ($config_file = $package_config['drupal-core-project-message'][$this->eventName . '-file'] ?? FALSE) {
      $file = $config_file;
    }

    $message = $package_config['drupal-core-project-message'][$this->eventName . '-message'] ?? [];

    if ($message) {
      $this->messageText = $message;
    }
    else {
      $this->messageText = $this->getMessageFromFile($file);
    }

    // Include structured support info from composer.json.
    if ($config_keys = $package_config['drupal-core-project-message']['include-keys'] ?? FALSE) {
      foreach ($config_keys as $config_key) {
        switch ($config_key) {
          case 'name':
            if ($homepage = $this->rootPackage->getName()) {
              $this->messageText[] = '  * Name: ' . $homepage;
            }
            break;
          case 'description':
            if ($homepage = $this->rootPackage->getDescription()) {
              $this->messageText[] = '  * Description: ' . $homepage;
            }
            break;
          case 'homepage':
            if ($homepage = $this->rootPackage->getHomepage()) {
              $this->messageText[] = '  * Homepage: ' . $homepage;
            }
            break;

          case 'support':
            if ($support = $this->rootPackage->getSupport()) {
              $this->messageText[] = '  * Support:';
              foreach ($support as $support_key => $support_value) {
                $this->messageText[] = '    * ' . $support_key . ': ' . $support_value;
              }
            }
            break;
        }
      }
    }

    return $this->messageText;
  }

  /**
   * Reads the message file as an array.
   *
   * @param string $file
   *   The file to read. Relative paths are relative to the project directory.
   *
   * @return string[]
   */
  protected function getMessageFromFile($file) {
    return file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];
  }

}
