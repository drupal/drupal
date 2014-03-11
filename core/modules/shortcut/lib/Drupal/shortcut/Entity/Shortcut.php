<?php

/**
 * @file
 * Contains \Drupal\shortcut\Entity\Shortcut.
 */

namespace Drupal\shortcut\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Url;
use Drupal\shortcut\ShortcutInterface;

/**
 * Defines the shortcut entity class.
 *
 * @ContentEntityType(
 *   id = "shortcut",
 *   label = @Translation("Shortcut link"),
 *   controllers = {
 *     "access" = "Drupal\shortcut\ShortcutAccessController",
 *     "form" = {
 *       "default" = "Drupal\shortcut\ShortcutFormController",
 *       "add" = "Drupal\shortcut\ShortcutFormController",
 *       "delete" = "Drupal\shortcut\Form\ShortcutDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   base_table = "shortcut",
 *   data_table = "shortcut_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "shortcut_set",
 *     "label" = "title"
 *   },
 *   links = {
 *     "delete-form" = "shortcut.link_delete",
 *     "edit-form" = "shortcut.link_edit"
 *   }
 * )
 */
class Shortcut extends ContentEntityBase implements ShortcutInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($link_title) {
    $this->set('title', $link_title);
   return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->get('route_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRouteName($route_name) {
    $this->set('route_name', $route_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParams() {
    $value = $this->get('route_parameters')->getValue();
    return reset($value);
  }

  /**
   * {@inheritdoc}
   */
  public function setRouteParams($route_parameters) {
    $this->set('route_parameters', array('value' => $route_parameters));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    if (!isset($values['shortcut_set'])) {
      $values['shortcut_set'] = 'default';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    $url = Url::createFromPath($this->path->value);
    $this->setRouteName($url->getRouteName());
    $this->setRouteParams($url->getRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = FieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the shortcut.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the shortcut.'))
      ->setReadOnly(TRUE);

    $fields['shortcut_set'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Shortcut set'))
      ->setDescription(t('The bundle of the shortcut.'))
      ->setSetting('target_type', 'shortcut_set')
      ->setRequired(TRUE);

    $fields['title'] = FieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The name of the shortcut.'))
      ->setTranslatable(TRUE);

    $fields['weight'] = FieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Weight among shortcuts in the same shortcut set.'));

    $fields['route_name'] = FieldDefinition::create('string')
      ->setLabel(t('Route name'))
      ->setDescription(t('The machine name of a defined Route this shortcut represents.'));

    $fields['route_parameters'] = FieldDefinition::create('map')
      ->setLabel(t('Route parameters'))
      ->setDescription(t('A serialized array of route parameters of this shortcut.'));

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of the shortcut.'));

    $fields['default_langcode'] = FieldDefinition::create('boolean')
      ->setLabel(t('Default language'))
      ->setDescription(t('Flag to indicate whether this is the default language.'));

    $fields['path'] = FieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('The computed shortcut path.'))
      ->setComputed(TRUE);

    $item_definition = $fields['path']->getItemDefinition();
    $item_definition->setClass('\Drupal\shortcut\ShortcutPathItem');
    $fields['path']->setItemDefinition($item_definition);

    return $fields;
  }

}
