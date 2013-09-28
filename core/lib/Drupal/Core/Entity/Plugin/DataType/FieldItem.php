<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\FieldItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the base plugin for deriving data types for field types.
 *
 * Note that the class only register the plugin, and is actually never used.
 * \Drupal\Core\Entity\Field\FieldItemBase is available for use as base class.
 *
 * @DataType(
 *   id = "field_item",
 *   label = @Translation("Field item"),
 *   list_class = "\Drupal\Core\Entity\Field\FieldItemList",
 *   derivative = "Drupal\Core\Entity\Plugin\DataType\Deriver\FieldItemDeriver"
 * )
 */
abstract class FieldItem {

}
