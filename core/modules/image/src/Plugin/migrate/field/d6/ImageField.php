<?php

namespace Drupal\image\Plugin\migrate\field\d6;

use Drupal\file\Plugin\migrate\field\d6\FileField;
use Drupal\migrate_drupal\Attribute\MigrateField;

// cspell:ignore imagefield
/**
 * MigrateField Plugin for Drupal 6 image fields.
 */
#[MigrateField(
  id: 'imagefield',
  core: [6],
  source_module: 'imagefield',
  destination_module: 'image',
)]
class ImageField extends FileField {}
