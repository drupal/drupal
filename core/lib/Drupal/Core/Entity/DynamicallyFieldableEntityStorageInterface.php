<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

/**
 * A storage that supports entity types with dynamic field definitions.
 *
 * A storage that implements this interface can react to the entity type's field
 * definitions changing, due to modules being installed or uninstalled, or via
 * field UI, or via code changes to the entity class.
 *
 * For example, configurable fields defined and exposed by field.module.
 */
interface DynamicallyFieldableEntityStorageInterface extends FieldableEntityStorageInterface, FieldStorageDefinitionListenerInterface, FieldDefinitionListenerInterface {

}
