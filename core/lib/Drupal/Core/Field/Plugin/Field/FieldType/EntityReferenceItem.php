<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableString;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Defines the 'entity_reference' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 * - target_bundle: (optional): If set, restricts the entity bundles which may
 *   may be referenced. May be set to an single bundle, or to an array of
 *   allowed bundles.
 *
 * @FieldType(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference"),
 *   description = @Translation("An entity field containing an entity reference."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ValidReference" = {}}
 * )
 */
class EntityReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'target_type' => \Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user',
      'target_bundle' => NULL,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'handler' => 'default:' . (\Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user'),
      'handler_settings' => array(),
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $target_type_info = \Drupal::entityManager()->getDefinition($settings['target_type']);

    $target_id_data_type = 'string';
    if ($target_type_info->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
      $id_definition = \Drupal::entityManager()->getBaseFieldDefinitions($settings['target_type'])[$target_type_info->getKey('id')];
      if ($id_definition->getType() === 'integer') {
        $target_id_data_type = 'integer';
      }
    }

    if ($target_id_data_type === 'integer') {
      $target_id_definition = DataReferenceTargetDefinition::create('integer')
        ->setLabel(new TranslatableString('@label ID', ['@label' => $target_type_info->getLabel()]))
        ->setSetting('unsigned', TRUE);
    }
    else {
      $target_id_definition = DataReferenceTargetDefinition::create('string')
        ->setLabel(new TranslatableString('@label ID', ['@label' => $target_type_info->getLabel()]));
    }
    $target_id_definition->setRequired(TRUE);
    $properties['target_id'] = $target_id_definition;

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableString('The referenced entity'))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($settings['target_type']))
      ->addConstraint('EntityType', $settings['target_type']);

    if (isset($settings['target_bundle'])) {
      $properties['entity']->addConstraint('Bundle', $settings['target_bundle']);
      // Set any further bundle constraints on the target definition as well,
      // such that it can derive more special data types if possible. For
      // example, "entity:node:page" instead of "entity:node".
      $properties['entity']->getTargetDefinition()
        ->addConstraint('Bundle', $settings['target_bundle']);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'target_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $target_type = $field_definition->getSetting('target_type');
    $target_type_info = \Drupal::entityManager()->getDefinition($target_type);
    $properties = static::propertyDefinitions($field_definition)['target_id'];
    if ($target_type_info->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface') && $properties->getDataType() === 'integer') {
      $columns = array(
        'target_id' => array(
          'description' => 'The ID of the target entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ),
      );
    }
    else {
      $columns = array(
        'target_id' => array(
          'description' => 'The ID of the target entity.',
          'type' => 'varchar_ascii',
          // If the target entities act as bundles for another entity type,
          // their IDs should not exceed the maximum length for bundles.
          'length' => $target_type_info->getBundleOf() ? EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
        ),
      );
    }

    $schema = array(
      'columns' => $columns,
      'indexes' => array(
        'target_id' => array('target_id'),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      // If either a scalar or an object was passed as the value for the item,
      // assign it to the 'entity' property since that works for both cases.
      $this->set('entity', $values, $notify);
    }
    else {
      parent::setValue($values, FALSE);
      // Support setting the field item with only one property, but make sure
      // values stay in sync if only property is passed.
      // NULL is a valid value, so we use array_key_exists().
      if (is_array($values) && array_key_exists('target_id', $values) && !isset($values['entity'])) {
        $this->onChange('target_id', FALSE);
      }
      elseif (is_array($values) && !array_key_exists('target_id', $values) && isset($values['entity'])) {
        $this->onChange('entity', FALSE);
      }
      elseif (is_array($values) && array_key_exists('target_id', $values) && isset($values['entity'])) {
        // If both properties are passed, verify the passed values match. The
        // only exception we allow is when we have a new entity: in this case
        // its actual id and target_id will be different, due to the new entity
        // marker.
        $entity_id = $this->get('entity')->getTargetIdentifier();
        // If the entity has been saved and we're trying to set both the
        // target_id and the entity values with a non-null target ID, then the
        // value for target_id should match the ID of the entity value.
        if (!$this->entity->isNew() && $values['target_id'] !== NULL && ($entity_id !== $values['target_id'])) {
          throw new \InvalidArgumentException('The target id and entity passed to the entity reference item do not match.');
        }
      }
      // Notify the parent if necessary.
      if ($notify && $this->parent) {
        $this->parent->onChange($this->getName());
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $values = parent::getValue();

    // If there is an unsaved entity, return it as part of the field item values
    // to ensure idempotency of getValue() / setValue().
    if ($this->hasNewEntity()) {
      $values['entity'] = $this->entity;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the target ID and the target property stay in sync.
    if ($property_name == 'entity') {
      $property = $this->get('entity');
      $target_id = $property->isTargetNew() ? NULL : $property->getTargetIdentifier();
      $this->writePropertyValue('target_id', $target_id);
    }
    elseif ($property_name == 'target_id') {
      $this->writePropertyValue('entity', $this->target_id);
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Avoid loading the entity by first checking the 'target_id'.
    if ($this->target_id !== NULL) {
      return FALSE;
    }
    if ($this->entity && $this->entity instanceof EntityInterface) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->hasNewEntity()) {
      // Save the entity if it has not already been saved by some other code.
      if ($this->entity->isNew()) {
        $this->entity->save();
      }
      // Make sure the parent knows we are updating this property so it can
      // react properly.
      $this->target_id = $this->entity->id();
    }
    if (!$this->isEmpty() && $this->target_id === NULL) {
      $this->target_id = $this->entity->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $manager = \Drupal::service('plugin.manager.entity_reference_selection');
    if ($referenceable = $manager->getSelectionHandler($field_definition)->getReferenceableEntities()) {
      $group = array_rand($referenceable);
      $values['target_id'] = array_rand($referenceable[$group]);
      return $values;
    }
  }

  /**
   * Determines whether the item holds an unsaved entity.
   *
   * This is notably used for "autocreate" widgets, and more generally to
   * support referencing freshly created entities (they will get saved
   * automatically as the hosting entity gets saved).
   *
   * @return bool
   *   TRUE if the item holds an unsaved entity.
   */
  public function hasNewEntity() {
    return !$this->isEmpty() && $this->target_id === NULL && $this->entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    $dependencies = [];
    if ($default_value = $field_definition->getDefaultValueLiteral()) {
      $target_entity_type = \Drupal::entityManager()->getDefinition($field_definition->getFieldStorageDefinition()->getSetting('target_type'));
      foreach ($default_value as $value) {
        if (is_array($value) && isset($value['target_uuid'])) {
          $entity = \Drupal::entityManager()->loadEntityByUuid($target_entity_type->id(), $value['target_uuid']);
          // If the entity does not exist do not create the dependency.
          // @see \Drupal\Core\Field\EntityReferenceFieldItemList::processDefaultValue()
          if ($entity) {
            $dependencies[$target_entity_type->getConfigDependencyKey()][] = $entity->getConfigDependencyName();
          }
        }
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(FieldDefinitionInterface $field_definition, array $dependencies) {
    $changed = FALSE;
    if ($default_value = $field_definition->getDefaultValueLiteral()) {
      $target_entity_type = \Drupal::entityManager()->getDefinition($field_definition->getFieldStorageDefinition()->getSetting('target_type'));
      foreach ($default_value as $key => $value) {
        if (is_array($value) && isset($value['target_uuid'])) {
          $entity = \Drupal::entityManager()->loadEntityByUuid($target_entity_type->id(), $value['target_uuid']);
          // @see \Drupal\Core\Field\EntityReferenceFieldItemList::processDefaultValue()
          if ($entity && isset($dependencies[$entity->getConfigDependencyKey()][$entity->getConfigDependencyName()])) {
            unset($default_value[$key]);
            $changed = TRUE;
          }
        }
      }
      if ($changed) {
        $field_definition->setDefaultValue($default_value);
      }
    }
    return $changed;
  }

}
