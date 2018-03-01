<?php

namespace Drupal\image\Plugin\migrate\field\d6;

use Drupal\file\Plugin\migrate\field\d6\FileField;

/**
 * @MigrateField(
 *   id = "imagefield",
 *   core = {6},
 *   source_module = "imagefield",
 *   destination_module = "image"
 * )
 */
class ImageField extends FileField {}
