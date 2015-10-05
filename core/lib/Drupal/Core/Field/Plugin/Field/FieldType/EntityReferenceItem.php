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
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Defines the 'entity_reference' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 *
 * @FieldType(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference"),
 *   description = @Translation("An entity field containing an entity reference."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_widget = "entity_reference_autocomplete",
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
        ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $target_type_info->getLabel()]))
        ->setSetting('unsigned', TRUE);
    }
    else {
      $target_id_definition = DataReferenceTargetDefinition::create('string')
        ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $target_type_info->getLabel()]));
    }
    $target_id_definition->setRequired(TRUE);
    $properties['target_id'] = $target_id_definition;

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableMarkup('The referenced entity'))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($settings['target_type']))
      // We can add a constraint for the target entity type. The list of
      // referenceable bundles is a field setting, so the corresponding
      // constraint is added dynamically in ::getConstraints().
      ->addConstraint('EntityType', $settings['target_type']);

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
  public function getConstraints() {
    $constraints = parent::getConstraints();
    list($current_handler) = explode(':', $this->getSetting('handler'), 2);
    if ($current_handler === 'default') {
      $handler_settings = $this->getSetting('handler_settings');
      if (isset($handler_settings['target_bundles'])) {
        $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
        $constraints[] = $constraint_manager->create('ComplexData', [
          'entity' => [
            'Bundle' => [
              'bundle' => $handler_settings['target_bundles'],
            ],
          ],
        ]);
      }
    }
    return $constraints;
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
    $dependencies = parent::calculateDependencies($field_definition);
    $manager = \Drupal::entityManager();
    $target_entity_type = $manager->getDefinition($field_definition->getFieldStorageDefinition()->getSetting('target_type'));

    // Depend on default values entity types configurations.
    if ($default_value = $field_definition->getDefaultValueLiteral()) {
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

    // Depend on target bundle configurations.
    $handler = $field_definition->getSetting('handler_settings');
    if (!empty($handler['target_bundles'])) {
      if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
        if ($storage = $manager->getStorage($bundle_entity_type_id)) {
          foreach ($storage->loadMultiple($handler['target_bundles']) as $bundle) {
            $dependencies[$bundle->getConfigDependencyKey()][] = $bundle->getConfigDependencyName();
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
    $changed = parent::onDependencyRemoval($field_definition, $dependencies);
    $entity_manager = \Drupal::entityManager();
    $target_entity_type = $entity_manager->getDefinition($field_definition->getFieldStorageDefinition()->getSetting('target_type'));

    // Try to update the default value config dependency, if possible.
    if ($default_value = $field_definition->getDefaultValueLiteral()) {
      foreach ($default_value as $key => $value) {
        if (is_array($value) && isset($value['target_uuid'])) {
          $entity = $entity_manager->loadEntityByUuid($target_entity_type->id(), $value['target_uuid']);
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

    // Update the 'target_bundles' handler setting if a bundle config dependency
    // has been removed.
    $bundles_changed = FALSE;
    $handler_settings = $field_definition->getSetting('handler_settings');
    if (!empty($handler_settings['target_bundles'])) {
      if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
        if ($storage = $entity_manager->getStorage($bundle_entity_type_id)) {
          foreach ($storage->loadMultiple($handler_settings['target_bundles']) as $bundle) {
            if (isset($dependencies[$bundle->getConfigDependencyKey()][$bundle->getConfigDependencyName()])) {
              unset($handler_settings['target_bundles'][$bundle->id()]);
              $bundles_changed = TRUE;

              // In case we deleted the only target bundle allowed by the field
              // we have to log a warning message because the field will not
              // function correctly anymore.
              if ($handler_settings['target_bundles'] === []) {
                \Drupal::logger('entity_reference')->critical('The %target_bundle bundle (entity type: %target_entity_type) was deleted. As a result, the %field_name entity reference field (entity_type: %entity_type, bundle: %bundle) no longer has any valid bundle it can reference. The field is not working correctly anymore and has to be adjusted.', [
                  '%target_bundle' => $bundle->label(),
                  '%target_entity_type' => $bundle->getEntityType()->getBundleOf(),
                  '%field_name' => $field_definition->getName(),
                  '%entity_type' => $field_definition->getTargetEntityTypeId(),
                  '%bundle' => $field_definition->getTargetBundle()
                ]);
              }
            }
          }
        }
      }
    }
    if ($bundles_changed) {
      $field_definition->setSetting('handler_settings', $handler_settings);
    }
    $changed |= $bundles_changed;

    return $changed;
  }

}
