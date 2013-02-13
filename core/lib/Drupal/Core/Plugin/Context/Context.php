<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Context\Context.
 */

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\Context as ComponentContext;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * A Drupal specific context wrapper class.
 *
 * The validate method is specifically overridden in order to support typed
 * data definitions instead of just class names in the contextual definitions
 * of plugins that extend ContextualPluginBase.
 */
class Context extends ComponentContext {

  /**
   * Overrides \Drupal\Component\Plugin\Context\Context::getContextValue().
   */
  public function getContextValue() {
    $typed_value = parent::getContextValue();
    // If the data is of a primitive type, directly return the plain value.
    // That way, e.g. a string will be return as plain PHP string.
    if ($typed_value instanceof \Drupal\Core\TypedData\TypedDataInterface) {
      $type_definition = typed_data()->getDefinition($typed_value->getType());
      if (!empty($type_definition['primitive type'])) {
        return $typed_value->getValue();
      }
    }
    return $typed_value;
  }

  /**
   * Gets the context value as typed data object.
   *
   * parent::getContextValue() does not do all the processing required to
   * return plain value of a TypedData object. This class overrides that method
   * to return the appropriate values from TypedData objects, but the object
   * itself can be useful as well, so this method is provided to allow for
   * access to the TypedData object. Since parent::getContextValue() already
   * does all the processing we need, we simply proxy to it here.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  public function getTypedContext() {
    return parent::getContextValue();
  }

  /**
   * Override for \Drupal\Component\Plugin\Context\Context::validate().
   */
  public function validate($value) {
    if (!empty($this->contextDefinition['type'])) {
      $typed_data_manager = new TypedDataManager();
      $typed_data = $typed_data_manager->create($this->contextDefinition, $value);
      // If we do have a typed data definition, validate it and return the
      // typed data instance instead.
      $violations = $typed_data->validate();
      if (count($violations) == 0) {
        return $typed_data;
      }
      throw new ContextException("The context passed could not be validated through typed data.");
    }
    return parent::validate($value);
  }

}
