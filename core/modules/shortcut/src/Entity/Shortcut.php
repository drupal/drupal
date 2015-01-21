<?php

/**
 * @file
 * Contains \Drupal\shortcut\Entity\Shortcut.
 */

namespace Drupal\shortcut\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\shortcut\ShortcutInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the shortcut entity class.
 *
 * @ContentEntityType(
 *   id = "shortcut",
 *   label = @Translation("Shortcut link"),
 *   handlers = {
 *     "access" = "Drupal\shortcut\ShortcutAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\shortcut\ShortcutForm",
 *       "add" = "Drupal\shortcut\ShortcutForm",
 *       "edit" = "Drupal\shortcut\ShortcutForm",
 *       "delete" = "Drupal\shortcut\Form\ShortcutDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "shortcut",
 *   data_table = "shortcut_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "shortcut_set",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/user-interface/shortcut/link/{shortcut}",
 *     "delete-form" = "/admin/config/user-interface/shortcut/link/{shortcut}/delete",
 *     "edit-form" = "/admin/config/user-interface/shortcut/link/{shortcut}",
 *   },
 *   list_cache_tags = { "config:shortcut_set_list" },
 *   bundle_entity_type = "shortcut_set"
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
  public function getUrl() {
    return new Url($this->getRouteName(), $this->getRouteParameters());
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
  public function getRouteParameters() {
    return $this->get('route_parameters')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setRouteParameters($route_parameters) {
    $this->set('route_parameters', $route_parameters);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    if (!isset($values['shortcut_set'])) {
      $values['shortcut_set'] = 'default';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // @todo fix PathValidatorInterface::getUrlIfValid() so we can use it
    //   here. The problem is that we need an exception, not a FALSE
    //   return value. https://www.drupal.org/node/2346695
    if ($this->path->value == '<front>') {
      $url = new Url($this->path->value);
    }
    else {
      $url = Url::createFromRequest(Request::create("/{$this->path->value}"));
    }
    $this->setRouteName($url->getRouteName());
    $this->setRouteParameters($url->getRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Entity::postSave() calls Entity::invalidateTagsOnSave(), which only
    // handles the regular cases. The Shortcut entity has one special case: a
    // newly created shortcut is *also* added to a shortcut set, so we must
    // invalidate the associated shortcut set's cache tag.
    if (!$update) {
      Cache::invalidateTags($this->getCacheTags());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the shortcut.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the shortcut.'))
      ->setReadOnly(TRUE);

    $fields['shortcut_set'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Shortcut set'))
      ->setDescription(t('The bundle of the shortcut.'))
      ->setSetting('target_type', 'shortcut_set')
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the shortcut.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
        'settings' => array(
          'size' => 40,
        ),
      ));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Weight among shortcuts in the same shortcut set.'));

    $fields['route_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Route name'))
      ->setDescription(t('The machine name of a defined Route this shortcut represents.'));

    $fields['route_parameters'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Route parameters'))
      ->setDescription(t('A serialized array of route parameters of this shortcut.'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The language code of the shortcut.'))
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('The computed shortcut path.'))
      ->setComputed(TRUE)
      ->addConstraint('ShortcutPath', [])
      ->setCustomStorage(TRUE);

    $item_definition = $fields['path']->getItemDefinition();
    $item_definition->setClass('\Drupal\shortcut\ShortcutPathItem');
    $fields['path']->setItemDefinition($item_definition);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->shortcut_set->entity->getCacheTags();
  }

}
