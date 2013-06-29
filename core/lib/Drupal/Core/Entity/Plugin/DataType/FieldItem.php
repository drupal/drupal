<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\FieldItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Plugin\PluginBase;

/**
 * Defines the base plugin definition for field type typed data types.
 *
 * @DataType(
 *   id = "field_item",
 *   label = @Translation("Field item"),
 *   list_class = "\Drupal\Core\Entity\Field\Field",
 *   derivative = "Drupal\Core\Entity\Plugin\DataType\FieldDataTypeDerivative"
 * )
 */
class FieldItem extends PluginBase {

}
