<?php

namespace Drupal\Core\Field\Plugin\DataType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\Plugin\DataType\Deriver\FieldItemDeriver;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;

/**
 * Defines the base plugin for deriving data types for field types.
 *
 * Note that the class only register the plugin, and is actually never used.
 * \Drupal\Core\Field\FieldItemBase is available for use as base class.
 */
#[DataType(
  id: "field_item",
  label: new TranslatableMarkup("Field item"),
  list_class: FieldItemList::class,
  deriver: FieldItemDeriver::class
)]
abstract class FieldItem {

}
