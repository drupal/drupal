<?php

declare(strict_types=1);

namespace Drupal\media_library_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'entity_reference_subclass' field type.
 */
#[FieldType(
  id: "entity_reference_subclass",
  label: new TranslatableMarkup("Entity reference subclass"),
  description: new TranslatableMarkup("An entity field containing an entity reference."),
  category: "reference",
  default_widget: "entity_reference_autocomplete",
  default_formatter: "entity_reference_label",
  list_class: EntityReferenceFieldItemList::class,
)]
class EntityReferenceItemSubclass extends EntityReferenceItem {
}
