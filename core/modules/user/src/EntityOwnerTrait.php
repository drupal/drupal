<?php

namespace Drupal\user;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a trait for entities that have an owner.
 */
trait EntityOwnerTrait {

  /**
   * Returns an array of base field definitions for entity owners.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to add the owner field to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of base field definitions.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   *   Thrown when the entity type does not implement EntityOwnerInterface or
   *   if it does not have an "owner" entity key.
   */
  public static function ownerBaseFieldDefinitions(EntityTypeInterface $entity_type) {
    if (!is_subclass_of($entity_type->getClass(), EntityOwnerInterface::class)) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not implement \Drupal\user\EntityOwnerInterface.');
    }
    if (!$entity_type->hasKey('owner')) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not have an "owner" entity key.');
    }

    return [
      $entity_type->getKey('owner') => BaseFieldDefinition::create('entity_reference')
        ->setLabel(new TranslatableMarkup('User ID'))
        ->setSetting('target_type', 'user')
        ->setTranslatable($entity_type->isTranslatable())
        ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('owner');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $key = $this->getEntityType()->getKey('owner');
    $this->set($key, $uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $key = $this->getEntityType()->getKey('owner');
    return $this->get($key)->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $key = $this->getEntityType()->getKey('owner');
    $this->set($key, $account);

    return $this;
  }

  /**
   * Default value callback for 'owner' base field.
   *
   * @return mixed
   *   A default value for the owner field.
   */
  public static function getDefaultEntityOwner() {
    return \Drupal::currentUser()->id();
  }

}
