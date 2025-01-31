<?php

namespace Drupal\Core\Field;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\TypedData\ListInterface;

/**
 * Interface for fields, being lists of field items.
 *
 * This interface must be implemented by every entity field, whereas contained
 * field items must implement the FieldItemInterface.
 * Some methods of the fields are delegated to the first contained item, in
 * particular get() and set() as well as their magic equivalences.
 *
 * Optionally, a typed data object implementing
 * Drupal\Core\TypedData\TypedDataInterface may be passed to
 * ArrayAccess::offsetSet() instead of a plain value.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\Field\FieldItemInterface
 */
interface FieldItemListInterface extends ListInterface, AccessibleInterface {

  /**
   * Gets the entity that field belongs to.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity object. If the entity is translatable and a specific
   *   translation is required, always request it by calling ::getTranslation()
   *   or ::getUntranslated() as the language of the returned object is not
   *   defined.
   */
  public function getEntity();

  /**
   * Sets the langcode of the field values held in the object.
   *
   * @param string $langcode
   *   The langcode.
   */
  public function setLangcode($langcode);

  /**
   * Gets the langcode of the field values held in the object.
   *
   * @return string
   *   The langcode.
   */
  public function getLangcode();

  /**
   * Gets the field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  public function getFieldDefinition();

  /**
   * Returns the array of field settings.
   *
   * @return array
   *   An array of key/value pairs.
   */
  public function getSettings();

  /**
   * Returns the value of a given field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($setting_name);

  /**
   * Contains the default access logic of this field.
   *
   * See \Drupal\Core\Entity\EntityAccessControlHandlerInterface::fieldAccess()
   * for the parameter documentation.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function defaultAccess($operation = 'view', ?AccountInterface $account = NULL);

  /**
   * Filters out empty field items and re-numbers the item deltas.
   *
   * @return $this
   */
  public function filterEmptyItems();

  /**
   * Magic method: Gets a property value of to the first field item.
   *
   * @see \Drupal\Core\Field\FieldItemInterface::__set()
   */
  public function __get($property_name);

  /**
   * Magic method: Sets a property value of the first field item.
   *
   * @see \Drupal\Core\Field\FieldItemInterface::__get()
   */
  public function __set($property_name, $value);

  /**
   * Magic method: Determines whether a property of the first field item is set.
   *
   * @see \Drupal\Core\Field\FieldItemInterface::__unset()
   */
  public function __isset($property_name);

  /**
   * Magic method: Unsets a property of the first field item.
   *
   * @see \Drupal\Core\Field\FieldItemInterface::__isset()
   */
  public function __unset($property_name);

  /**
   * Defines custom presave behavior for field values.
   *
   * This method is called during the process of saving an entity, just before
   * item values are written into storage.
   *
   * @see \Drupal\Core\Field\FieldItemInterface::preSave()
   */
  public function preSave();

  /**
   * Defines custom post-save behavior for field values.
   *
   * This method is called during the process of saving an entity, just after
   * item values are written into storage.
   *
   * @param bool $update
   *   Specifies whether the entity is being updated or created.
   *
   * @return bool
   *   Whether field items should be rewritten to the storage as a consequence
   *   of the logic implemented by the custom behavior.
   *
   * @see \Drupal\Core\Field\FieldItemInterface::postSave()
   */
  public function postSave($update);

  /**
   * Defines custom delete behavior for field values.
   *
   * This method is called during the process of deleting an entity, just before
   * values are deleted from storage.
   */
  public function delete();

  /**
   * Defines custom revision delete behavior for field values.
   *
   * This method is called from during the process of deleting an entity
   * revision, just before the field values are deleted from storage. It is only
   * called for entity types that support revisioning.
   */
  public function deleteRevision();

  /**
   * Returns a renderable array for the field items.
   *
   * @param string|array $display_options
   *   Can be either the name of a view mode, or an array of display settings.
   *   See EntityViewBuilderInterface::viewField() for more information.
   *
   * @return array
   *   A renderable array for the field values.
   *
   * @see \Drupal\Core\Entity\EntityViewBuilderInterface::viewField()
   * @see \Drupal\Core\Field\FieldItemInterface::view()
   */
  public function view($display_options = []);

  /**
   * Populates a specified number of field items with valid sample data.
   *
   * @param int $count
   *   The number of items to create.
   */
  public function generateSampleItems($count = 1);

  /**
   * Returns a form for the default value input.
   *
   * Invoked from \Drupal\field_ui\Form\FieldConfigEditForm to allow
   * administrators to configure instance-level default value.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the field default value.
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state);

  /**
   * Validates the submitted default value.
   *
   * Invoked from \Drupal\field_ui\Form\FieldConfigEditForm to allow
   * administrators to configure instance-level default value.
   *
   * @param array $element
   *   The default value form element.
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state);

  /**
   * Processes the submitted default value.
   *
   * Invoked from \Drupal\field_ui\Form\FieldConfigEditForm to allow
   * administrators to configure instance-level default value.
   *
   * @param array $element
   *   The default value form element.
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The field default value.
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state);

  /**
   * Processes the default value before being applied.
   *
   * Defined or configured default values of a field might need some processing
   * in order to be a valid runtime value for the field type; e.g., a date field
   * could process the defined value of 'NOW' to a valid date.
   *
   * @param array $default_value
   *   The unprocessed default value defined for the field, as a numerically
   *   indexed array of items, each item being an array of property/value pairs.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which the default value is generated.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The definition of the field.
   *
   * @return array
   *   The return default value for the field.
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition);

  /**
   * Determines equality to another object implementing FieldItemListInterface.
   *
   * This method is usually used by the storage to check for not computed
   * value changes, which will be saved into the storage.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $list_to_compare
   *   The field item list to compare to.
   *
   * @return bool
   *   TRUE if the field item lists are equal, FALSE if not.
   */
  public function equals(FieldItemListInterface $list_to_compare);

  /**
   * Determines whether the field has relevant changes.
   *
   * This is for example used to determine if a revision of an entity has
   * changes in a given translation. Unlike
   * \Drupal\Core\Field\FieldItemListInterface::equals(), this can report
   * that for example an untranslatable field, despite being changed and
   * therefore technically affecting all translations, is only internal metadata
   * or only affects a single translation.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $original_items
   *   The original field items to compare against.
   * @param string $langcode
   *   The language that should be checked.
   *
   * @return bool
   *   TRUE if the field has relevant changes, FALSE if not.
   */
  public function hasAffectingChanges(FieldItemListInterface $original_items, $langcode);

}
