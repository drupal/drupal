<?php

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
 *   list_class = "\Drupal\path\Plugin\Field\FieldType\PathFieldItemList",
 *   constraints = {"PathAlias" = {}},
 * )
 */
class PathItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['alias'] = DataDefinition::create('string')
      ->setLabel(t('Path alias'));
    $properties['pid'] = DataDefinition::create('integer')
      ->setLabel(t('Path id'));
    $properties['langcode'] = DataDefinition::create('string')
      ->setLabel(t('Language Code'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return ($this->alias === NULL || $this->alias === '') && ($this->pid === NULL || $this->pid === '') && ($this->langcode === NULL || $this->langcode === '');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->alias !== NULL) {
      $this->alias = trim($this->alias);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $path_alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $entity = $this->getEntity();

    // If specified, rely on the langcode property for the language, so that the
    // existing language of an alias can be kept. That could for example be
    // unspecified even if the field/entity has a specific langcode.
    $alias_langcode = ($this->langcode && $this->pid) ? $this->langcode : $this->getLangcode();

    // If we have an alias, we need to create or update a path alias entity.
    if ($this->alias) {
      if (!$update || !$this->pid) {
        $path_alias = $path_alias_storage->create([
          'path' => '/' . $entity->toUrl()->getInternalPath(),
          'alias' => $this->alias,
          'langcode' => $alias_langcode,
        ]);
        $path_alias->save();
        $this->pid = $path_alias->id();
      }
      elseif ($this->pid) {
        $path_alias = $path_alias_storage->load($this->pid);

        if ($this->alias != $path_alias->getAlias()) {
          $path_alias->setAlias($this->alias);
          $path_alias->save();
        }
      }
    }
    elseif ($this->pid && !$this->alias) {
      // Otherwise, delete the old alias if the user erased it.
      $path_alias = $path_alias_storage->load($this->pid);
      if ($entity->isDefaultRevision()) {
        $path_alias_storage->delete([$path_alias]);
      }
      else {
        $path_alias_storage->deleteRevision($path_alias->getRevisionID());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['alias'] = '/' . str_replace(' ', '-', strtolower($random->sentences(3)));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'alias';
  }

}
