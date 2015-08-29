<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\InfoParserDynamic.
 */

namespace Drupal\Core\Extension;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Parses dynamic .info.yml files that might change during the page request.
 */
class InfoParserDynamic implements InfoParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse($filename) {
    if (!file_exists($filename)) {
      $parsed_info = array();
    }
    else {
      try {
        $parsed_info = Yaml::decode(file_get_contents($filename));
      }
      catch (InvalidDataTypeException $e) {
        $message = SafeMarkup::format("Unable to parse !file: !error", array('!file' => $filename, '!error' => $e->getMessage()));
        throw new InfoParserException($message);
      }
      $missing_keys = array_diff($this->getRequiredKeys(), array_keys($parsed_info));
      if (!empty($missing_keys)) {
        $message = SafeMarkup::format('Missing required keys (!missing_keys) in !file.', array('!missing_keys' => implode(', ', $missing_keys), '!file' => $filename));
        throw new InfoParserException($message);
      }
      if (isset($parsed_info['version']) && $parsed_info['version'] === 'VERSION') {
        $parsed_info['version'] = \Drupal::VERSION;
      }
    }
    return $parsed_info;
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
