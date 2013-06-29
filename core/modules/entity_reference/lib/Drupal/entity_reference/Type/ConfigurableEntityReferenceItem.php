<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Type\ConfigurableEntityReferenceItem.
 */

namespace Drupal\entity_reference\Type;

use Drupal\Core\Entity\Plugin\DataType\EntityReferenceItem;
use Drupal\field\Plugin\Type\FieldType\ConfigEntityReferenceItemBase;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemInterface;

/**
 * Defines the 'entity_reference_configurable' entity field item.
 *
 * Extends the Core 'entity_reference' entity field item with properties for
 * revision ids, labels (for autocreate) and access.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - target_type: The entity type to reference.
 */
class ConfigurableEntityReferenceItem extends ConfigEntityReferenceItemBase implements ConfigFieldItemInterface {

}
