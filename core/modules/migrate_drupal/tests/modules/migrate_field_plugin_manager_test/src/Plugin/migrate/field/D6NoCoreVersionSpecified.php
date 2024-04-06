<?php

namespace Drupal\migrate_field_plugin_manager_test\Plugin\migrate\field;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

#[MigrateField(
  id: 'd6_no_core_version_specified',
  source_module: 'foo',
  destination_module: 'bar',
)]
class D6NoCoreVersionSpecified extends FieldPluginBase {}
