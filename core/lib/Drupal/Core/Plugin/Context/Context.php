<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Context\Context.
 */

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\Context as ComponentContext;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListInterface;
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
    // If the typed data is complex, pass it on as typed data. Else pass on its
    // plain value, such that e.g. a string will be directly returned as PHP
    // string.
    $is_complex = $typed_value instanceof ComplexDataInterface;
    if (!$is_complex && $typed_value instanceof ListInterface) {
      $is_complex = $typed_value[0] instanceof ComplexDataInterface;
    }
    if ($typed_value instanceof TypedDataInterface && !$is_complex) {
      return $typed_value->getValue();
    }
    return $typed_value;
  }

  /**
   * Implements \Drupal\Component\Plugin\Context\ContextInterface::setContextValue().
   */
  public function setContextValue($value) {
    // Make sure the value set is a typed data object.
    if (!empty($this->contextDefinition['type']) && !$value instanceof TypedDataInterface) {
      $value = \Drupal::typedDataManager()->create(new DataDefinition($this->contextDefinition), $value);
    }
    parent::setContextValue($value);
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
   * Implements \Drupal\Component\Plugin\Context\ContextInterface::getConstraints().
   */
  public function getConstraints() {
    if (!empty($this->contextDefinition['type'])) {
      // If we do have typed data, leverage it for getting constraints.
      return $this->getTypedContext()->getConstraints();
    }
    return parent::getConstraints();
  }

  /**
   * Overrides \Drupal\Component\Plugin\Context\Context::getConstraints().
   */
  public function validate() {
    // If the context is typed data, defer to its validation.
    if (!empty($this->contextDefinition['type'])) {
      return $this->getTypedContext()->validate();
    }
    return parent::validate();
  }
}
