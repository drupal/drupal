<?php

namespace Drupal\Core\Render\Builder;

/**
 * Define a base that all theme builders extend.
 */
abstract class FormElementBuilderBase extends BuilderBase implements FormElementBuilderBaseInterface {

  /**
   * {@inheritdoc}
   */
  public function setElementValidate($value) {
    $this->set('element_validate', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setValueCallback($value) {
    $this->set('value_callback', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTree($value) {
    $this->set('tree', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcess($value) {
    $this->set('process', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStates($value) {
    $this->set('states', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern($value) {
    $this->set('pattern', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setArrayParents($value) {
    $this->set('array_parents', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setParents($value) {
    $this->set('parents', $value);
    return $this;
  }

}
