<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\Field\FieldType\PathItem.
 */

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'path' entity field type.
 *
 * @FieldType(
 *   id = "path",
 *   label = @Translation("Path"),
 *   description = @Translation("An entity field containing a path alias and related data."),
 *   configurable = FALSE
 * )
 */
class PathItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['alias'] = DataDefinition::create('string')
        ->setLabel(t('Path alias'));
    $properties['pid'] = DataDefinition::create('string')
        ->setLabel(t('Path id'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->alias = trim($this->alias);
  }

  /**
   * {@inheritdoc}
   */
  public function insert() {
    if ($this->alias) {
      $entity = $this->getEntity();

      // Ensure fields for programmatic executions.
      $langcode = $entity->language()->id;

      if ($path = \Drupal::service('path.crud')->save($entity->getSystemPath(), $this->alias, $langcode)) {
        $this->pid = $path['pid'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    // Delete old alias if user erased it.
    if ($this->pid && !$this->alias) {
      \Drupal::service('path.crud')->delete(array('pid' => $this->pid));
    }
    // Only save a non-empty alias.
    elseif ($this->alias) {
      $entity = $this->getEntity();

      // Ensure fields for programmatic executions.
      $langcode = $entity->language()->id;

      \Drupal::service('path.crud')->save($entity->getSystemPath(), $this->alias, $langcode, $this->pid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete all aliases associated with this entity.
    $entity = $this->getEntity();
    \Drupal::service('path.crud')->delete(array('source' => $entity->getSystemPath()));
  }

}
