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

  public static function decode($raw) {
    $class = Settings::get('yaml_parser_class');
    if ($class && $class !== TRUE) {
      return $class::decode($raw);
    }

    return parent::decode($raw);
  }

}
