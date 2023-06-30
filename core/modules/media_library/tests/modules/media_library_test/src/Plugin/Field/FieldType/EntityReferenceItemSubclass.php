<?php

namespace Drupal\media_library_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Plugin implementation of the 'entity_reference_subclass' field type.
 *
 * @FieldType(
 *   id = "entity_reference_subclass",
 *   label = @Translation("Entity reference subclass"),
 *   description = @Translation("An entity field containing an entity reference."),
 *   category = "reference",
 *   default_widget = "entity_reference_autocomplete",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class EntityReferenceItemSubclass extends EntityReferenceItem {
}
