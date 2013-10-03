<?php

/**
 * @file
 * Definition of Drupal\Core\Template\AttributeString.
 */

namespace Drupal\Core\Template;

use Drupal\Component\Utility\String;

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
 */
class AttributeString extends AttributeValueBase {

  /**
   * Implements the magic __toString() method.
   */
  public function __toString() {
    $this->printed = TRUE;
    return String::checkPlain($this->value);
  }

}
