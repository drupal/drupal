<?php

namespace Drupal\Core\Template;

use Drupal\Component\Attribute\AttributeCollection;
use Drupal\Component\Utility\NestedArray;

/**
 * Helper class to deal with mixed array and AttributeCollection operations.
 *
 * This class contains static methods only and is not meant to be instantiated.
 */
class AttributeHelper {

  /**
   * This class should not be instantiated.
   */
  private function __construct() {
  }

  /**
   * Checks if the given attribute collection has an attribute.
   *
   * @param string $name
   *   The name of the attribute to check for.
   * @param \Drupal\Component\Attribute\AttributeCollection|array $collection
   *   An AttributeCollection object or an array of attributes.
   *
   * @return bool
   *   TRUE if the attribute exists, FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   When the input $collection is neither an AttributeCollection object nor
   *   an array.
   */
  public static function attributeExists($name, $collection) {
    if ($collection instanceof AttributeCollection) {
      return $collection->hasAttribute($name);
    }
    elseif (is_array($collection)) {
      return array_key_exists($name, $collection);
    }
    throw new \InvalidArgumentException('Invalid collection argument');
  }

  /**
   * Merges two attribute collections.
   *
   * @param \Drupal\Component\Attribute\AttributeCollection|array $a
   *   First Attribute object or array to merge. The returned value type will
   *   be the same as the type of this argument.
   * @param \Drupal\Component\Attribute\AttributeCollection|array $b
   *   Second Attribute object or array to merge.
   *
   * @return \Drupal\Component\Attribute\AttributeCollection|array
   *   The merged attributes, as an Attribute object or an array.
   *
   * @throws \InvalidArgumentException
   *   If at least one collection argument is neither an AttributeCollection
   *   object nor an array.
   */
  public static function mergeCollections($a, $b) {
    if (!($a instanceof AttributeCollection || is_array($a)) || !($b instanceof AttributeCollection || is_array($b))) {
      throw new \InvalidArgumentException('Invalid collection argument');
    }
    // If both collections are arrays, just merge them.
    if (is_array($a) && is_array($b)) {
      return NestedArray::mergeDeep($a, $b);
    }
    // If at least one collections is an AttributeCollection object, merge
    // through AttributeCollection::merge.
    $merge_a = $a instanceof AttributeCollection ? $a : new AttributeCollection($a);
    $merge_b = $b instanceof AttributeCollection ? $b : new AttributeCollection($b);
    $merge_a->merge($merge_b);
    return $a instanceof AttributeCollection ? $merge_a : $merge_a->toArray();
  }

}
