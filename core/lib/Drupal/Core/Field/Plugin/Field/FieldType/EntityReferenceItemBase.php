<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;

/**
 * Base class for field items referencing other entities.
 *
 * Any field type that is an entity reference should extend from this class in
 * order to remain backwards compatible with any changes added in the future
 * to EntityReferenceItemInterface.
 */
abstract class EntityReferenceItemBase extends FieldItemBase implements EntityReferenceItemInterface {

}
