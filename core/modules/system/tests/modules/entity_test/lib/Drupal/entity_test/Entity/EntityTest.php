<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTest.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test",
 *   label = @Translation("Test entity"),
 *   controllers = {
 *     "list_builder" = "Drupal\entity_test\EntityTestListBuilder",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "entity_test",
 *   fieldable = TRUE,
 *   field_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name"
 *   },
 *   links = {
 *     "canonical" = "entity_test.render",
 *     "edit-form" = "entity_test.edit_entity_test",
 *     "delete-form" = "entity_test.delete_entity_test",
 *     "admin-form" = "entity_test.admin_entity_test"
 *   }
 * )
 */
class EntityTest extends ContentEntityBase implements EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    if (empty($values['type'])) {
      $values['type'] = $storage->getEntityTypeId();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = FieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the test entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the test entity.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of the test entity.'));

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the test entity.'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ));

    // @todo: Add allowed values validation.
    $fields['type'] = FieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The bundle of the test entity.'))
      ->setRequired(TRUE);

    $fields['user_id'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The ID of the associated user.'))
      ->setSettings(array('target_type' => 'user'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * Sets the name.
   *
   * @param string $name
   *   Name of the entity.
   *
   * @return $this
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * Returns the name.
   *
   * @return string
   */
  public function getName() {
    return $this->get('name')->value;
  }

}
