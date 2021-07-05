<?php
namespace Drupal\Core\Template;

use Drupal\Component\Attribute\AttributeString as ComponentAttributeString;

@trigger_error('\Drupal\Core\Template\AttributeString is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeString instead. See https://www.drupal.org/node/3070485', E_USER_DEPRECATED);

/**
 * A class that represents most standard HTML attributes.
 *
 * To use with the Attribute class, set the key to be the attribute name
 * and the value the attribute value.
 * @code
 *  $attributes = new Attribute(array());
 *  $attributes['id'] = 'socks';
 *  $attributes['style'] = 'background-color:white';
 *  echo '<cat ' . $attributes . '>';
 *  // Produces: <cat id="socks" style="background-color:white">.
 * @endcode
 *
 * @see \Drupal\Core\Template\Attribute
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Component\Attribute\AttributeString instead.
 *
 * @see https://www.drupal.org/node/3070485
 */
class AttributeString extends ComponentAttributeString {
}
