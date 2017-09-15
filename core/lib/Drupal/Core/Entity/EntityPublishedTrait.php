<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a trait for published status.
 */
trait EntityPublishedTrait {

  /**
   * Returns an array of base field definitions for publishing status.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to add the publishing status field to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of base field definitions.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   *   Thrown when the entity type does not implement EntityPublishedInterface
   *   or if it does not have a "published" entity key.
   */
  public static function publishedBaseFieldDefinitions(EntityTypeInterface $entity_type) {
    if (!is_subclass_of($entity_type->getClass(), EntityPublishedInterface::class)) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not implement \Drupal\Core\Entity\EntityPublishedInterface.');
    }
    if (!$entity_type->hasKey('published')) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not have a "published" entity key.');
    }

    return [
      $entity_type->getKey('published') => BaseFieldDefinition::create('boolean')
        ->setLabel(new TranslatableMarkup('Published'))
        ->setRevisionable(TRUE)
        ->setTranslatable(TRUE)
        ->setDefaultValue(TRUE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    $key = $this->getEntityType()->getKey('published');
    return (bool) $this->get($key)->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    if ($published !== NULL) {
      @trigger_error('The $published parameter is deprecated since version 8.3.x and will be removed in 9.0.0.', E_USER_DEPRECATED);
      $value = (bool) $published;
    }
    else {
      $value = TRUE;
    }
    $key = $this->getEntityType()->getKey('published');
    $this->set($key, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished() {
    $key = $this->getEntityType()->getKey('published');
    $this->set($key, FALSE);

    return $this;
  }

}
