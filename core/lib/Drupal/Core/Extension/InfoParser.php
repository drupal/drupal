<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\InfoParser.
 */

namespace Drupal\Core\Extension;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Parses extension .info.yml files.
 */
class InfoParser implements InfoParserInterface {

  /**
   * Array of all info keyed by filename.
   *
   * @var array
   */
  protected static $parsedInfos = array();

  /**
   * {@inheritdoc}
   */
  public function parse($filename) {
    if (!isset(static::$parsedInfos[$filename])) {
      if (!file_exists($filename)) {
        static::$parsedInfos[$filename] = array();
      }
      else {
        try {
          static::$parsedInfos[$filename] = Yaml::decode(file_get_contents($filename));
        }
        catch (InvalidDataTypeException $e) {
          $message = SafeMarkup::format("Unable to parse !file: !error", array('!file' => $filename, '!error' => $e->getMessage()));
          throw new InfoParserException($message);
        }
        $missing_keys = array_diff($this->getRequiredKeys(), array_keys(static::$parsedInfos[$filename]));
        if (!empty($missing_keys)) {
          $message = SafeMarkup::format('Missing required keys (!missing_keys) in !file.', array('!missing_keys' => implode(', ', $missing_keys), '!file' => $filename));
          throw new InfoParserException($message);
        }
        if (isset(static::$parsedInfos[$filename]['version']) && static::$parsedInfos[$filename]['version'] === 'VERSION') {
          static::$parsedInfos[$filename]['version'] = \Drupal::VERSION;
        }
      }
    }
    return static::$parsedInfos[$filename];
  }

  /**
   * Returns an array of keys required to exist in .info.yml file.
   *
   * @return array
   *   An array of required keys.
   */
  protected function getRequiredKeys() {
    return array('type', 'core', 'name');
  }

}
