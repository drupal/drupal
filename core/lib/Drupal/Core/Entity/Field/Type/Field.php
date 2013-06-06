<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\Field.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\user\UserInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\ItemList;

/**
 * Represents an entity field; that is, a list of field item objects.
 *
 * An entity field is a list of field items, which contain only primitive
 * properties or entity references. Note that even single-valued entity
 * fields are represented as list of items, however for easy access to the
 * contained item the entity field delegates __get() and __set() calls
 * directly to the first item.
 *
 * @see \Drupal\Core\Entity\Field\FieldInterface
 */
class Field extends ItemList implements FieldInterface {

  /**
   * Numerically indexed array of field items, implementing the
   * FieldItemInterface.
   *
   * @var array
   */
  protected $list = array();

  /**
   * Overrides TypedData::__construct().
   */
  public function __construct(array $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    // Always initialize one empty item as most times a value for at least one
    // item will be present. That way prototypes created by
    // \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance() will
    // already have this field item ready for use after cloning.
    $this->list[0] = $this->createItem(0);
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
   * Overrides \Drupal\Core\TypedData\ItemList::setValue().
   */
  public function setValue($values, $notify = TRUE) {
    // Notify the parent of any changes to be made.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
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
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    return $this->offsetGet(0)->getPropertyDefinition($name);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    return $this->offsetGet(0)->getPropertyDefinitions();
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::__get().
   */
  public function __get($property_name) {
    return $this->offsetGet(0)->__get($property_name);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::get().
   */
  public function get($property_name) {
    return $this->offsetGet(0)->get($property_name);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::__set().
   */
  public function __set($property_name, $value) {
    $this->offsetGet(0)->__set($property_name, $value);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::__isset().
   */
  public function __isset($property_name) {
    return $this->offsetGet(0)->__isset($property_name);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::__unset().
   */
  public function __unset($property_name) {
    return $this->offsetGet(0)->__unset($property_name);
  }

  /**
   * Implements \Drupal\Core\TypedData\AccessibleInterface::access().
   */
  public function access($operation = 'view', UserInterface $account = NULL) {
    global $user;
    if (!isset($account) && $user->uid) {
      $account = user_load($user->uid);
    }
    // Get the default access restriction that lives within this field.
    $access = $this->defaultAccess($operation, $account);
    // Invoke hook and collect grants/denies for field access from other
    // modules. Our default access flag is masked under the ':default' key.
    $grants = array(':default' => $access);
    $hook_implementations = \Drupal::moduleHandler()->getImplementations('entity_field_access');
    foreach ($hook_implementations as $module) {
      $grants = array_merge($grants, array($module => module_invoke($module, 'entity_field_access', $operation, $this, $account)));
    }
    // Also allow modules to alter the returned grants/denies.
    $context = array(
      'operation' => $operation,
      'field' => $this,
      'account' => $account,
    );
    drupal_alter('entity_field_access', $grants, $context);

    // One grant being FALSE is enough to deny access immediately.
    if (in_array(FALSE, $grants, TRUE)) {
      return FALSE;
    }
    // At least one grant has the explicit opinion to allow access.
    if (in_array(TRUE, $grants, TRUE)) {
      return TRUE;
    }
    // All grants are NULL and have no opinion - deny access in that case.
    return FALSE;
  }

  /**
   * Contains the default access logic of this field.
   *
   * See \Drupal\Core\TypedData\AccessibleInterface::access() for the parameter
   * doucmentation. This method can be overriden by field sub classes to provide
   * a different default access logic. That allows them to inherit the complete
   * access() method which contains the access hook invocation logic.
   *
   * @return bool
   *   TRUE if access to this field is allowed per default, FALSE otherwise.
   */
  public function defaultAccess($operation = 'view', UserInterface $account = NULL) {
    // Grant access per default.
    return TRUE;
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
}
