<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\field\field_type\LegacyConfigField.
 */

namespace Drupal\field\Plugin\field\field_type;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\FieldType\ConfigField;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Field class for legacy field types.
 *
 * This acts as a temporary BC layer for field types that have not been
 * converted to Plugins, and bridges new methods to the old-style hook_field_*()
 * callbacks.
 *
 * This class is not discovered by the annotations reader, but referenced by
 * the Drupal\field\Plugin\Discovery\LegacyDiscoveryDecorator.
 *
 * @todo Remove once all core field types have been converted (see
 * http://drupal.org/node/2014671).
 */
class LegacyConfigField extends ConfigField {

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $violations = parent::validate();

    // Filter out empty items (legacy hook_field_validate() implementations
    // used to receive pruned items).
    $this->filterEmptyValues();

    $legacy_errors = array();
    $this->legacyCallback('validate', array(&$legacy_errors));

    $entity = $this->getParent();
    $langcode = $entity->language()->id;

    if (isset($legacy_errors[$this->getInstance()->getField()->id()][$langcode])) {
      foreach ($legacy_errors[$this->getInstance()->getField()->id()][$langcode] as $delta => $item_errors) {
        foreach ($item_errors as $item_error) {
          // We do not have the information about which column triggered the
          // error, so assume the first column...
          $column = key($this->getInstance()->getField()->getColumns());
          $violations->add(new ConstraintViolation($item_error['message'], $item_error['message'], array(), $this, $delta . '.' . $column, $this->offsetGet($delta)->get($column)->getValue(), NULL, $item_error['error']));
        }
      }
    }

    return $violations;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // Filter out empty items.
    $this->filterEmptyValues();

    $this->legacyCallback('presave');
  }

  /**
   * {@inheritdoc}
   */
  public function insert() {
    $this->legacyCallback('insert');
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $this->legacyCallback('update');
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->legacyCallback('delete');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    $this->legacyCallback('delete_revision');
  }

  /**
   * Calls the legacy callback for a given field type "hook", if it exists.
   *
   * @param string $hook
   *   The name of the hook, e.g. 'presave', 'validate'.
   */
  protected function legacyCallback($hook, $args = array()) {
    $definition = $this->getPluginDefinition();
    $module = $definition['module'];
    $callback = "{$module}_field_{$hook}";
    if (function_exists($callback)) {
      $entity = $this->getParent();
      $langcode = $entity->language()->id;

      // We need to remove the empty "prototype" item here.
      // @todo Revisit after http://drupal.org/node/1988492.
      $this->filterEmptyValues();
      // Legcacy callbacks alter $items by reference.
      $items = (array) $this->getValue(TRUE);
      $args = array_merge(array(
        $entity,
        $this->getInstance()->getField(),
        $this->getInstance(),
        $langcode,
        &$items
      ), $args);
      call_user_func_array($callback, $args);
      $this->setValue($items);
    }
  }

}
