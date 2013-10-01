<?php

/**
 * @file
 * Definition of Drupal\entity_test\Entity\EntityTest.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\Language;

/**
 * Defines the test entity class.
 *
 * @EntityType(
 *   id = "entity_test",
 *   label = @Translation("Test entity"),
 *   module = "entity_test",
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController",
 *     "list" = "Drupal\entity_test\EntityTestListController",
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestFormController"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   base_table = "entity_test",
 *   fieldable = TRUE,
 *   field_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *   },
 *   menu_base_path = "entity-test/manage/%entity_test"
 * )
 */
class EntityTest extends ContentEntityBase {

  /**
   * The entity ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $id;

  /**
   * The entity UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $uuid;

  /**
   * The bundle of the test entity.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $type;

  /**
   * The name of the test entity.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $name;

  /**
   * The associated user.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $user_id;

  /**
   * Initialize the object. Invoked upon construction and wake up.
   */
  protected function init() {
    parent::init();
    // We unset all defined properties, so magic getters apply.
    unset($this->id);
    unset($this->uuid);
    unset($this->name);
    unset($this->user_id);
    unset($this->type);
  }

  /**
   * Overrides Drupal\entity\Entity::label().
   */
  public function label($langcode = Language::LANGCODE_DEFAULT) {
    $info = $this->entityInfo();
    if (isset($info['entity_keys']['label']) && $info['entity_keys']['label'] == 'name') {
      return $this->getTranslation($langcode)->name->value;
    }
    else {
      return parent::label($langcode);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields['id'] = array(
      'label' => t('ID'),
      'description' => t('The ID of the test entity.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The UUID of the test entity.'),
      'type' => 'uuid_field',
    );
    $fields['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The language code of the test entity.'),
      'type' => 'language_field',
    );
    $fields['name'] = array(
      'label' => t('Name'),
      'description' => t('The name of the test entity.'),
      'type' => 'string_field',
      'translatable' => TRUE,
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 32)),
      ),
    );
    $fields['type'] = array(
      'label' => t('Type'),
      'description' => t('The bundle of the test entity.'),
      'type' => 'string_field',
      'required' => TRUE,
      // @todo: Add allowed values validation.
    );
    $fields['user_id'] = array(
      'label' => t('User ID'),
      'description' => t('The ID of the associated user.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
      'translatable' => TRUE,
    );
    return $fields;
  }
}
