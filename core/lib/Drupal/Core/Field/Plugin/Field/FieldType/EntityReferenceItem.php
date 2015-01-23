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
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;

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
 *   no_ui = TRUE,
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ValidReference" = {}}
 * )
 */
class EntityReferenceItem extends FieldItemBase {

  /**
   * Marker value to identify a newly created entity.
   *
   * @var int
   */
  protected static $NEW_ENTITY_MARKER = -1;

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
      'handler' => 'default',
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $target_type_info = \Drupal::entityManager()->getDefinition($settings['target_type']);

    if ($target_type_info->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
      // @todo: Lookup the entity type's ID data type and use it here.
      // https://drupal.org/node/2107249
      $target_id_definition = DataDefinition::create('integer')
        ->setLabel(t('@label ID', array($target_type_info->getLabel())))
        ->setSetting('unsigned', TRUE);
    }
    else {
      $target_id_definition = DataDefinition::create('string')
        ->setLabel(t('@label ID', array($target_type_info->getLabel())));
    }
    $target_id_definition->setRequired(TRUE);
    $properties['target_id'] = $target_id_definition;

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(t('The referenced entity'))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($settings['target_type']));

    if (isset($settings['target_bundle'])) {
      $properties['entity']->getTargetDefinition()->addConstraint('Bundle', $settings['target_bundle']);
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

    if ($target_type_info->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
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
          'type' => 'varchar',
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
      if (isset($values['target_id']) && !isset($values['entity'])) {
        $this->onChange('target_id', FALSE);
      }
      elseif (!isset($values['target_id']) && isset($values['entity'])) {
        $this->onChange('entity', FALSE);
      }
      elseif (isset($values['target_id']) && isset($values['entity'])) {
        // If both properties are passed, verify the passed values match. The
        // only exception we allow is when we have a new entity: in this case
        // its actual id and target_id will be different, due to the new entity
        // marker.
        $entity_id = $this->get('entity')->getTargetIdentifier();
        if ($entity_id != $values['target_id'] && ($values['target_id'] != static::$NEW_ENTITY_MARKER || !$this->entity->isNew())) {
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
      $target_id = $property->isTargetNew() ? static::$NEW_ENTITY_MARKER : $property->getTargetIdentifier();
      $this->writePropertyValue('target_id', $target_id);
    }
    elseif ($property_name == 'target_id' && $this->target_id != static::$NEW_ENTITY_MARKER) {
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
    return $this->target_id === static::$NEW_ENTITY_MARKER;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    $dependencies = [];
    if (is_array($field_definition->default_value) && count($field_definition->default_value)) {
      $target_entity_type = \Drupal::entityManager()->getDefinition($field_definition->getFieldStorageDefinition()->getSetting('target_type'));
      foreach ($field_definition->default_value as $default_value) {
        if (is_array($default_value) && isset($default_value['target_uuid'])) {
          $entity = \Drupal::entityManager()->loadEntityByUuid($target_entity_type->id(), $default_value['target_uuid']);
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

}
