<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\Field\FieldType\PathItem.
 */

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'path' entity field type.
 *
 * @FieldType(
 *   id = "path",
 *   label = @Translation("Path"),
 *   description = @Translation("An entity field containing a path alias and related data."),
 *   no_ui = TRUE,
 *   default_widget = "path",
 *   list_class = "\Drupal\path\Plugin\Field\FieldType\PathFieldItemList"
 * )
 */
class PathItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['alias'] = DataDefinition::create('string')
      ->setLabel(t('Path alias'));
    $properties['pid'] = DataDefinition::create('string')
      ->setLabel(t('Path id'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
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

      if ($path = \Drupal::service('path.alias_storage')->save($entity->getSystemPath(), $this->alias, $this->getLangcode())) {
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
      \Drupal::service('path.alias_storage')->delete(array('pid' => $this->pid));
    }
    // Only save a non-empty alias.
    elseif ($this->alias) {
      $entity = $this->getEntity();

      \Drupal::service('path.alias_storage')->save($entity->getSystemPath(), $this->alias, $this->getLangcode(), $this->pid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete all aliases associated with this entity.
    $entity = $this->getEntity();
    \Drupal::service('path.alias_storage')->delete(array('source' => $entity->getSystemPath()));
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['alias'] = str_replace(' ', '-', strtolower($random->sentences(3)));
    return $values;
  }

}
