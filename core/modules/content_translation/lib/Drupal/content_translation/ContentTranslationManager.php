<?php

/**
 * @file
 * Contains \Drupal\content_translation\ContentTranslationManager.
 */

namespace Drupal\content_translation;

use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Provides common functionality for content translation.
 */
class ContentTranslationManager implements ContentTranslationManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ContentTranslationManageAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->entityManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported($entity_type_id) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    return $entity_type->isTranslatable() && $entity_type->hasLinkTemplate('drupal:content-translation-overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    $supported_types = array();
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($this->isSupported($entity_type_id)) {
        $supported_types[$entity_type_id] = $entity_type;
      }
    }
    return $supported_types;
  }

}
