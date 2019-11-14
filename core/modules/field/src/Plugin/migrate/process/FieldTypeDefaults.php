<?php

namespace Drupal\field\Plugin\migrate\process;

@trigger_error('The field_type_defaults process plugin is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Use d6_field_type_defaults or d7_field_type_defaults instead. See https://www.drupal.org/node/2944589.', E_USER_DEPRECATED);

use Drupal\field\Plugin\migrate\process\d6\FieldTypeDefaults as D6FieldTypeDefaults;

/**
 * BC Layer.
 *
 * @MigrateProcessPlugin(
 *   id = "field_type_defaults"
 * )
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0.
 * Use d6_field_type_defaults or d7_field_type_defaults instead.
 *
 * @see https://www.drupal.org/node/2944589
 */
class FieldTypeDefaults extends D6FieldTypeDefaults {}
