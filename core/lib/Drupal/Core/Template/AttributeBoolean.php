<?php

namespace Drupal\Core\Template;

use Drupal\Component\Attribute\AttributeBoolean as ComponentAttributeBoolean;

@trigger_error('\Drupal\Core\Template\AttributeBoolean is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeBoolean instead. See https://www.drupal.org/node/3070485', E_USER_DEPRECATED);

/**
 * A class that defines a type of boolean HTML attribute.
 *
 * Boolean HTML attributes are not attributes with values of TRUE/FALSE.
 * They are attributes that if they exist in the tag, they are TRUE.
 * Examples include selected, disabled, checked, readonly.
 *
 * To set a boolean attribute on the Attribute class, set it to TRUE.
 * @code
 *  $attributes = new Attribute();
 *  $attributes['disabled'] = TRUE;
 *  echo '<select' . $attributes . '/>';
 *  // produces <select disabled>;
 *  $attributes['disabled'] = FALSE;
 *  echo '<select' . $attributes . '/>';
 *  // produces <select>;
 * @endcode
 *
 * @see \Drupal\Core\Template\Attribute
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Component\Attribute\AttributeBoolean instead.
 *
 * @see https://www.drupal.org/node/3070485
 */
class AttributeBoolean extends ComponentAttributeBoolean {
}
