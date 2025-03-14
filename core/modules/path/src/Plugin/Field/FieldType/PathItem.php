<?php

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'path' entity field type.
 */
#[FieldType(
  id: "path",
  label: new TranslatableMarkup("Path"),
  description: new TranslatableMarkup("An entity field containing a path alias and related data."),
  default_widget: "path",
  no_ui: TRUE,
  list_class: PathFieldItemList::class,
  constraints: ["PathAlias" => []],
)]
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
    $alias = $this->get('alias')->getValue();
    $pid = $this->get('pid')->getValue();
    $langcode = $this->get('langcode')->getValue();

    return ($alias === NULL || $alias === '') && ($pid === NULL || $pid === '') && ($langcode === NULL || $langcode === '');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $alias = $this->get('alias')->getValue();
    if ($alias !== NULL) {
      $this->set('alias', trim($alias));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $path_alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $entity = $this->getEntity();
    $alias = $this->get('alias')->getValue();
    $pid = $this->get('pid')->getValue();
    $langcode = $this->get('langcode')->getValue();

    // If specified, rely on the langcode property for the language, so that the
    // existing language of an alias can be kept. That could for example be
    // unspecified even if the field/entity has a specific langcode.
    $alias_langcode = ($langcode && $pid) ? $langcode : $this->getLangcode();

    // If we have an alias, we need to create or update a path alias entity.
    if ($alias) {
      if (!$update || !$pid) {
        $path_alias = $path_alias_storage->create([
          'path' => '/' . $entity->toUrl()->getInternalPath(),
          'alias' => $alias,
          'langcode' => $alias_langcode,
        ]);
        $path_alias->save();
        $this->set('pid', $path_alias->id());
      }
      else {
        $path_alias = $path_alias_storage->load($pid);

        if ($alias != $path_alias->getAlias()) {
          $path_alias->setAlias($alias);
          $path_alias->save();
        }
      }
    }
    elseif ($pid) {
      // Otherwise, delete the old alias if the user erased it.
      $path_alias = $path_alias_storage->load($pid);
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
