<?php

namespace Drupal\Core\Serialization;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Yaml as ComponentYaml;

/**
 * Provides a YAML serialization implementation.
 *
 * Allow settings to override the YAML implementation resolution.
 */
class Yaml extends ComponentYaml {

  /**
   * {@inheritdoc}
   */
  protected static function getSerializer() {
    // Allow settings.php to override the YAML serializer.
    if (!isset(static::$serializer) &&
      $class = Settings::get('yaml_parser_class')) {

      static::$serializer = $class;
    }
    return parent::getSerializer();
  }

}
