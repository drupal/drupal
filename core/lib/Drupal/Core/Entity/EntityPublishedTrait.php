<?php

namespace Drupal\Core\Entity;

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
   *   Array of base field definitions.
   */
  public static function publishedBaseFieldDefinitions(EntityTypeInterface $entity_type) {
    $key = $entity_type->hasKey('status') ? $entity_type->getKey('status') : 'status';
    return [$key => BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Publishing status'))
      ->setDescription(new TranslatableMarkup('A boolean indicating the published state.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)];
  }

  /**
   * Returns the published status of the entity.
   *
   * @return bool
   *   The published status of the entity.
   */
  public function isPublished() {
    $status = $this->getEntityKey('status');
    return (bool) (isset($status) ? $status : $this->get('status')->value);
  }

  /**
   * Sets the entity as published or not published.
   *
   * @param bool $published
   *   A boolean value denoting the published status.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface $this
   *   The Content Entity object.
   */
  public function setPublished($published) {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $key = $this->getEntityType()->getKey('status') ?: 'status';
    // @todo: Replace values with constants from EntityPublishedInterface or
    // similar when introduced. https://www.drupal.org/node/2811667
    $this->set($key, $published ? 1 : 0);
    return $this;
  }

}
