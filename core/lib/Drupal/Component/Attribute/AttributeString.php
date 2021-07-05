<?php

namespace Drupal\Component\Attribute;

use Drupal\Component\Utility\Html;

/**
 * A class that represents most standard HTML attributes.
 *
 * To use with the AttributeCollection class, set the key to be the attribute
 * name and the value the attribute value.
 * @code
 *  $attributes = new AttributeCollection([]);
 *  $attributes['id'] = 'socks';
 *  $attributes['style'] = 'background-color:white';
 *  echo '<cat ' . $attributes . '>';
 *  // Produces: <cat id="socks" style="background-color:white">.
 * @endcode
 *
 * @see \Drupal\Component\Attribute\AttributeCollection
 */
class AttributeString extends AttributeValueBase {

  /**
   * Implements the magic __toString() method.
   */
  public function __toString() {
    return Html::escape($this->value);
  }

}
