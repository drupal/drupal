<?php

namespace Drupal\Core\Field\Plugin\DataType;

/**
 * Defines the base plugin for deriving data types for field types.
 *
 * Note that the class only register the plugin, and is actually never used.
 * \Drupal\Core\Field\FieldItemBase is available for use as base class.
 *
 * @DataType(
 *   id = "field_item",
 *   label = @Translation("Field item"),
 *   list_class = "\Drupal\Core\Field\FieldItemList",
 *   deriver = "Drupal\Core\Field\Plugin\DataType\Deriver\FieldItemDeriver"
 * )
 */
abstract class FieldItem {

}
