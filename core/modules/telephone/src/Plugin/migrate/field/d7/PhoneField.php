<?php

namespace Drupal\telephone\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "phone",
 *   type_map = {
 *     "phone" = "telephone",
 *   },
 *   core = {7}
 * )
 */
class PhoneField extends FieldPluginBase {}
