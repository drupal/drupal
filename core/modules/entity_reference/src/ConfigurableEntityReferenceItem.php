<?php

/**
 * @file
 * Contains \Drupal\entity_reference\ConfigurableEntityReferenceItem.
 */

namespace Drupal\entity_reference;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Deprecated. Alternative implementation of the 'entity_reference' field type.
 *
 * @deprecated in Drupal 8.0.x and will be removed in Drupal 9.0.x. Use
 *   \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem instead.
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem
 */
class ConfigurableEntityReferenceItem extends EntityReferenceItem { }
