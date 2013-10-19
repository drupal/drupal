<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldItemList.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\ItemList;
use Drupal\Core\Language\Language;

/**
 * Represents an entity field; that is, a list of field item objects.
 *
 * An entity field is a list of field items, which contain only primitive
 * properties or entity references. Note that even single-valued entity
 * fields are represented as list of items, however for easy access to the
 * contained item the entity field delegates __get() and __set() calls
 * directly to the first item.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - default_value: (optional) If set, the default value to apply to the field.
 */
class FieldItemList extends ItemList implements FieldItemListInterface {

  /**
   * Numerically indexed array of field items, implementing the
   * FieldItemInterface.
   *
   * @var array
   */
  protected $list = array();

  /**
   * The langcode of the field values held in the object.
   *
   * @var string
   */
  protected $langcode = Language::LANGCODE_DEFAULT;

  /**
   * Overrides TypedData::__construct().
   */
  public function __construct(array $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->definition['field_name'] = $name;
    // Always initialize one empty item as most times a value for at least one
    // item will be present. That way prototypes created by
    // \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance() will
    // already have this field item ready for use after cloning.
    $this->list[0] = $this->createItem(0);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->getParent();
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    $this->langcode = $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    return new FieldDefinition($this->definition);
  }

  /**
   * {@inheritdoc}
   */
  public function filterEmptyValues() {
    if (isset($this->list)) {
      $this->list = array_values(array_filter($this->list, function($item) {
        return !$item->isEmpty();
      }));
    }
  }

  /**
   * {@inheritdoc}
   * @todo Revisit the need when all entity types are converted to NG entities.
   */
  public function getValue($include_computed = FALSE) {
    if (isset($this->list)) {
      $values = array();
      foreach ($this->list as $delta => $item) {
        $values[$delta] = $item->getValue($include_computed);
      }
      return $values;
    }
  }

  /**
   * Overrides \Drupal\Core\TypedData\ItemList::setValue().
   */
  public function setValue($values, $notify = TRUE) {
    if (!isset($values) || $values === array()) {
      $this->list = $values;
    }
    else {
      // Support passing in only the value of the first item.
      if (!is_array($values) || !is_numeric(current(array_keys($values)))) {
        $values = array(0 => $values);
      }

      // Clear the values of properties for which no value has been passed.
      if (isset($this->list)) {
        $this->list = array_intersect_key($this->list, $values);
      }

      // Set the values.
      foreach ($values as $delta => $value) {
        if (!is_numeric($delta)) {
          throw new \InvalidArgumentException('Unable to set a value with a non-numeric delta in a list.');
        }
        elseif (!isset($this->list[$delta])) {
          $this->list[$delta] = $this->createItem($delta, $value);
        }
        else {
          $this->list[$delta]->setValue($value, FALSE);
        }
      }
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinition($name) {
    return $this->offsetGet(0)->getPropertyDefinition($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return $this->offsetGet(0)->getPropertyDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function __get($property_name) {
    return $this->offsetGet(0)->__get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    return $this->offsetGet(0)->get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function __set($property_name, $value) {
    $this->offsetGet(0)->__set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($property_name) {
    return $this->offsetGet(0)->__isset($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($property_name) {
    return $this->offsetGet(0)->__unset($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    $access_controller = \Drupal::entityManager()->getAccessController($this->getParent()->entityType());
    return $access_controller->fieldAccess($operation, $this->getFieldDefinition(), $account, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    // Grant access per default.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // @todo Remove getDefaultValue() and directly call
    // FieldDefinition::getFieldDefaultValue() here, once
    // https://drupal.org/node/2047229 is fixed.
    $value = $this->getDefaultValue();
    // NULL or array() mean "no default value", but  0, '0' and the empty string
    // are valid default values.
    if (!isset($value) || (is_array($value) && empty($value))) {
      // Create one field item and apply defaults.
      $this->offsetGet(0)->applyDefaultValue(FALSE);
    }
    else {
      $this->setValue($value, $notify);
    }
    return $this;
  }

  /**
   * Returns the default value for the field.
   *
   * @return array
   *   The default value for the field.
   */
  protected function getDefaultValue() {
    if (isset($this->definition['settings']['default_value'])) {
      return $this->definition['settings']['default_value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    // Constraints usually apply to the field item, but required does make
    // sense on the field only. So we special-case it to apply to the field for
    // now.
    // @todo: Separate list and list item definitions to separate constraints.
    $constraints = array();
    if (!empty($this->definition['required'])) {
      $constraints[] = \Drupal::typedData()->getValidationConstraintManager()->create('NotNull', array());
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // Filter out empty items.
    $this->filterEmptyValues();

    $this->delegateMethod('presave');
  }

  /**
   * {@inheritdoc}
   */
  public function insert() {
    $this->delegateMethod('insert');
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $this->delegateMethod('update');
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->delegateMethod('delete');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    $this->delegateMethod('deleteRevision');
  }

  /**
   * Calls a method on each FieldItem.
   *
   * @param string $method
   *   The name of the method.
   */
  protected function delegateMethod($method) {
    if (isset($this->list)) {
      foreach ($this->list as $item) {
        $item->{$method}();
      }
    }
  }

}
