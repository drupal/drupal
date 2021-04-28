<?php

namespace Drupal\Core\Render\Builder;

/**
 * Defines methods found on the base builder class.
 */
interface FormElementBuilderBaseInterface extends BuilderBaseInterface {

  /**
   * Set the 'element_validate' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setElementValidate($value);

  /**
   * Set the 'value_callback' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setValueCallack($value);

  /**
   * Set the 'tree' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setTree($value);

  /**
   * Set the 'process' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setProcess($value);

  /**
   * Set the 'states' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setStates($value);

  /**
   * Set the 'pattern' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setPattern($value);

  /**
   * Set the 'array_parents' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setArrayParents($value);

  /**
   * Set the 'parents' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setParents($value);

}
