<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Parser.
 */

namespace Drupal\Core\Config\Schema;

/**
 * Parser.
 */
class Parser {

  /**
   * Parse configuration data against schema data.
   */
  static function parse($data, $definition, $name = NULL, $parent = NULL) {
    // Set default type depending on data and context.
    if (!isset($definition['type'])) {
      if (is_array($data) || !$context) {
        $definition += array('type' => 'any');
      }
      else {
        $definition += array('type' => 'str');
      }
    }
    // Create typed data object.
    config_typed()->create($definition, $data, $name, $parent);
  }

  /**
   * Validate configuration data against schema data.
   */
  static function validate($config_data, $schema_data) {
    return self::parse($config_data, $schema_data)->validate();
  }

  static function getDefinition($type, $data) {
    return config_definition($type);
  }
}

