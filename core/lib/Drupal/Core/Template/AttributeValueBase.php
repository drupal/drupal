<?php

namespace Drupal\Core\Template;

use Drupal\Component\Attribute\AttributeValueBase as ComponentAttributeValueBase;

@trigger_error('\Drupal\Core\Template\AttributeValueBase is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeValueBase instead. See https://www.drupal.org/node/3070485', E_USER_DEPRECATED);

/**
 * Defines the base class for an attribute type.
 *
 * @see \Drupal\Core\Template\Attribute
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Component\Attribute\AttributeString instead.
 *
 * @see https://www.drupal.org/node/3070485
 */
abstract class AttributeValueBase extends ComponentAttributeValueBase {
}
