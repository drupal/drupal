<?php

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Gives us a chance to set per field defaults.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('d7_field_type_defaults')]
class FieldTypeDefaults extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value) && isset($value[1])) {
      return $value[1];
    }
    return $value;
  }

}
