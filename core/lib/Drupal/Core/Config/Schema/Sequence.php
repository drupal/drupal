<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Sequence.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Core\TypedData\ListInterface;

/**
 * Defines a configuration element of type Sequence.
 */
class Sequence extends ArrayElement implements ListInterface {

  /**
   * Data definition
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $itemDefinition;

  /**
   * {@inheritdoc}
   */
  protected function parse() {
    // Creates a new data definition object for each item from the generic type
    // definition array and actual configuration data for that item. Type
    // definitions may contain variables to be replaced and those depend on
    // each item's data.
    $definition = isset($this->definition['sequence'][0]) ? $this->definition['sequence'][0] : array();
    $elements = array();
    foreach ($this->value as $key => $value) {
      $data_definition =  $this->buildDataDefinition($definition, $value, $key);
      $elements[$key] = $this->parseElement($key, $value, $data_definition);
    }
    return $elements;
  }

  /**
   * Implements Drupal\Core\TypedData\ListInterface::isEmpty().
   */
  public function isEmpty() {
    return empty($this->value);
  }

  /**
   * Implements Drupal\Core\TypedData\ListInterface::getItemDefinition().
   */
  public function getItemDefinition() {
    if (!isset($this->itemDefinition)) {
      $definition = isset($this->definition['sequence'][0]) ? $this->definition['sequence'][0] : array();
      $this->itemDefinition = $this->buildDataDefinition($definition, NULL);
    }
    return $this->itemDefinition;
  }

  /**
   * Implements \Drupal\Core\TypedData\ListInterface::onChange().
   */
  public function onChange($delta) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $elements = $this->getElements();
    return $elements[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function first() {
    return $this->get(0);
  }

  /**
   * {@inheritdoc}
   */
  public function set($index, $value) {
    $this->offsetSet($index, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($index) {
    $this->offsetUnset($index);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendItem($value = NULL) {
    $offset = $this->count();
    $this->offsetSet($offset, $value);
    return $this->offsetGet($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function filter($callback) {
    $this->value = array_filter($this->value, $callback);
    unset($this->elements);
    return $this;
  }

}
