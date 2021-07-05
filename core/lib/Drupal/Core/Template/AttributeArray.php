<?php
namespace Drupal\Core\Template;

use Drupal\Component\Attribute\AttributeArray as ComponentAttributeArray;

@trigger_error('\Drupal\Core\Template\AttributeArray is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeArray instead. See https://www.drupal.org/node/3070485', E_USER_DEPRECATED);

/**
 * A class that defines a type of Attribute that can be added to as an array.
 *
 * To use with Attribute, the array must be specified.
 * Correct:
 * @code
 *  $attributes = new Attribute();
 *  $attributes['class'] = array();
 *  $attributes['class'][] = 'cat';
 * @endcode
 * Incorrect:
 * @code
 *  $attributes = new Attribute();
 *  $attributes['class'][] = 'cat';
 * @endcode
 *
 * @see \Drupal\Core\Template\Attribute
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Component\Attribute\AttributeArray instead.
 *
 * @see https://www.drupal.org/node/3070485
 */
class AttributeArray extends ComponentAttributeArray {
}
