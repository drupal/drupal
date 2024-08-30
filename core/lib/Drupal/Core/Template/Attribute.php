<?php

namespace Drupal\Core\Template;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Collects, sanitizes, and renders HTML attributes.
 *
 * To use, optionally pass in an associative array of defined attributes, or
 * add attributes using array syntax. For example:
 * @code
 *  $attributes = new Attribute(['id' => 'socks']);
 *  $attributes['class'] = ['black-cat', 'white-cat'];
 *  $attributes['class'][] = 'black-white-cat';
 *  echo '<cat' . $attributes . '>';
 *  // Produces <cat id="socks" class="black-cat white-cat black-white-cat">
 * @endcode
 *
 * $attributes always prints out all the attributes. For example:
 * @code
 *  $attributes = new Attribute(['id' => 'socks']);
 *  $attributes['class'] = ['black-cat', 'white-cat'];
 *  $attributes['class'][] = 'black-white-cat';
 *  echo '<cat class="cat ' . $attributes['class'] . '"' . $attributes . '>';
 *  // Produces <cat class="cat black-cat white-cat black-white-cat" id="socks" class="cat black-cat white-cat black-white-cat">
 * @endcode
 *
 * When printing out individual attributes to customize them within a Twig
 * template, use the "without" filter to prevent attributes that have already
 * been printed from being printed again. For example:
 * @code
 * <cat class="{{ attributes.class }} my-custom-class"{{ attributes|without('class') }}>
 * @endcode
 * Produces:
 * @code
 * <cat class="cat black-cat white-cat black-white-cat my-custom-class" id="socks">
 * @endcode
 *
 * The attribute keys and values are automatically escaped for output with
 * Html::escape(). No protocol filtering is applied, so when using user-entered
 * input as a value for an attribute that expects a URI (href, src, ...),
 * UrlHelper::stripDangerousProtocols() should be used to ensure dangerous
 * protocols (such as 'javascript:') are removed. For example:
 * @code
 *  $path = 'javascript:alert("xss");';
 *  $path = UrlHelper::stripDangerousProtocols($path);
 *  $attributes = new Attribute(['href' => $path]);
 *  echo '<a' . $attributes . '>';
 *  // Produces <a href="alert(&quot;xss&quot;);">
 * @endcode
 *
 * The attribute values are considered plain text and are treated as such. If a
 * safe HTML string is detected, it is converted to plain text with
 * PlainTextOutput::renderFromHtml() before being escaped. For example:
 * @code
 *   $value = t('Highlight the @tag tag', ['@tag' => '<em>']);
 *   $attributes = new Attribute(['value' => $value]);
 *   echo '<input' . $attributes . '>';
 *   // Produces <input value="Highlight the &lt;em&gt; tag">
 * @endcode
 *
 * @see \Drupal\Component\Utility\Html::escape()
 * @see \Drupal\Component\Render\PlainTextOutput::renderFromHtml()
 * @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
 */
class Attribute implements \ArrayAccess, \IteratorAggregate, MarkupInterface {

  /**
   * Stores the attribute data.
   *
   * @var \Drupal\Core\Template\AttributeValueBase[]
   */
  protected $storage = [];

  /**
   * Constructs a \Drupal\Core\Template\Attribute object.
   *
   * @param array $attributes
   *   An associative array of key-value pairs to be converted to attributes.
   */
  public function __construct($attributes = []) {
    foreach ($attributes as $name => $value) {
      $this->offsetSet($name, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($name): mixed {
    if (isset($this->storage[$name])) {
      return $this->storage[$name];
    }
    // The 'class' array key is expected to be itself an array, and therefore
    // can be accessed using array append syntax before it has been initialized.
    if ($name === 'class') {
      // Initialize the class attribute as an empty array if not set.
      $this->offsetSet('class', []);
      return $this->storage['class'];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($name, $value): void {
    $this->storage[$name] = $this->createAttributeValue($name, $value);
  }

  /**
   * Creates the different types of attribute values.
   *
   * @param string $name
   *   The attribute name.
   * @param mixed $value
   *   The attribute value.
   *
   * @return \Drupal\Core\Template\AttributeValueBase
   *   An AttributeValueBase representation of the attribute's value.
   */
  protected function createAttributeValue($name, $value) {
    // If the value is already an AttributeValueBase object,
    // return a new instance of the same class, but with the new name.
    if ($value instanceof AttributeValueBase) {
      $class = get_class($value);
      return new $class($name, $value->value());
    }
    // An array value or 'class' attribute name are forced to always be an
    // AttributeArray value for consistency.
    if ($name == 'class' && !is_array($value)) {
      // Cast the value to string in case it implements MarkupInterface.
      $value = [(string) $value];
    }
    if (is_array($value)) {
      // Cast the value to an array if the value was passed in as a string.
      // @todo Decide to fix all the broken instances of class as a string
      // in core or cast them.
      $value = new AttributeArray($name, $value);
    }
    elseif (is_bool($value)) {
      $value = new AttributeBoolean($name, $value);
    }
    // As a development aid, we allow the value to be a safe string object.
    elseif ($value instanceof MarkupInterface) {
      // Attributes are not supposed to display HTML markup, so we just convert
      // the value to plain text.
      $value = PlainTextOutput::renderFromHtml($value);
      $value = new AttributeString($name, $value);
    }
    elseif (!is_object($value)) {
      $value = new AttributeString($name, $value);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($name): void {
    unset($this->storage[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($name): bool {
    return isset($this->storage[$name]);
  }

  /**
   * Adds classes or merges them on to array of existing CSS classes.
   *
   * @param string|array ...
   *   CSS classes to add to the class attribute array.
   *
   * @return $this
   */
  public function addClass() {
    $args = func_get_args();
    if ($args) {
      $classes = [];
      foreach ($args as $arg) {
        // Merge the values passed in from the classes array.
        // The argument is cast to an array to support comma separated single
        // values or one or more array arguments.
        $classes[] = (array) $arg;
      }
      $classes = array_merge(...$classes);

      // Merge if there are values, just add them otherwise.
      if (isset($this->storage['class']) && $this->storage['class'] instanceof AttributeArray) {
        // Merge the values passed in from the class value array.
        $classes = array_merge($this->storage['class']->value(), $classes);
        $this->storage['class']->exchangeArray($classes);
      }
      else {
        $this->offsetSet('class', $classes);
      }
    }

    return $this;
  }

  /**
   * Sets values for an attribute key.
   *
   * @param string $attribute
   *   Name of the attribute.
   * @param string|array $value
   *   Value(s) to set for the given attribute key.
   *
   * @return $this
   */
  public function setAttribute($attribute, $value) {
    $this->offsetSet($attribute, $value);

    return $this;
  }

  /**
   * Checks if the storage has an attribute with the given name.
   *
   * @param string $name
   *   The name of the attribute to check for.
   *
   * @return bool
   *   Returns TRUE if the attribute exists, or FALSE otherwise.
   */
  public function hasAttribute($name) {
    return array_key_exists($name, $this->storage);
  }

  /**
   * Removes an attribute from an Attribute object.
   *
   * @param string|array ...
   *   Attributes to remove from the attribute array.
   *
   * @return $this
   */
  public function removeAttribute() {
    $args = func_get_args();
    foreach ($args as $arg) {
      // Support arrays or multiple arguments.
      if (is_array($arg)) {
        foreach ($arg as $value) {
          unset($this->storage[$value]);
        }
      }
      else {
        unset($this->storage[$arg]);
      }
    }

    return $this;
  }

  /**
   * Removes argument values from array of existing CSS classes.
   *
   * @param string|array ...
   *   CSS classes to remove from the class attribute array.
   *
   * @return $this
   */
  public function removeClass() {
    // With no class attribute, there is no need to remove.
    if (isset($this->storage['class']) && $this->storage['class'] instanceof AttributeArray) {
      $args = func_get_args();
      $classes = [];
      foreach ($args as $arg) {
        // Merge the values passed in from the classes array.
        // The argument is cast to an array to support comma separated single
        // values or one or more array arguments.
        $classes[] = (array) $arg;
      }
      $classes = array_merge(...$classes);

      // Remove the values passed in from the value array. Use array_values() to
      // ensure that the array index remains sequential.
      $classes = array_values(array_diff($this->storage['class']->value(), $classes));
      $this->storage['class']->exchangeArray($classes);
    }
    return $this;
  }

  /**
   * Gets the class attribute value if set.
   *
   * This method is implemented to take precedence over hasClass() for Twig 2.0.
   *
   * @return \Drupal\Core\Template\AttributeValueBase
   *   The class attribute value if set.
   *
   * @see twig_get_attribute()
   */
  public function getClass() {
    return $this->offsetGet('class');
  }

  /**
   * Checks if the class array has the given CSS class.
   *
   * @param string $class
   *   The CSS class to check for.
   *
   * @return bool
   *   Returns TRUE if the class exists, or FALSE otherwise.
   */
  public function hasClass($class) {
    if (isset($this->storage['class']) && $this->storage['class'] instanceof AttributeArray) {
      return in_array($class, $this->storage['class']->value());
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements the magic __toString() method.
   */
  public function __toString() {
    $return = '';
    /** @var \Drupal\Core\Template\AttributeValueBase $value */
    foreach ($this->storage as $value) {
      $rendered = $value->render();
      if ($rendered) {
        $return .= ' ' . $rendered;
      }
    }
    return $return;
  }

  /**
   * Returns all storage elements as an array.
   *
   * @return array
   *   An associative array of attributes.
   */
  public function toArray() {
    $return = [];
    foreach ($this->storage as $name => $value) {
      $return[$name] = $value->value();
    }

    return $return;
  }

  /**
   * Implements the magic __clone() method.
   */
  public function __clone() {
    foreach ($this->storage as $name => $value) {
      $this->storage[$name] = clone $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \ArrayIterator {
    return new \ArrayIterator($this->storage);
  }

  /**
   * Returns the whole array.
   */
  public function storage() {
    return $this->storage;
  }

  /**
   * Returns a representation of the object for use in JSON serialization.
   *
   * @return string
   *   The safe string content.
   */
  public function jsonSerialize(): string {
    return (string) $this;
  }

  /**
   * Merges an Attribute object into the current storage.
   *
   * @param \Drupal\Core\Template\Attribute $collection
   *   The Attribute object to merge.
   *
   * @return $this
   */
  public function merge(Attribute $collection) {
    $merged_attributes = NestedArray::mergeDeep($this->toArray(), $collection->toArray());
    foreach ($merged_attributes as $name => $value) {
      $this->storage[$name] = $this->createAttributeValue($name, $value);
    }
    return $this;
  }

}
