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
  public function isSupported($entity_type) {
    $info = $this->entityManager->getDefinition($entity_type);
    return $info->isTranslatable() && $info->hasLinkTemplate('drupal:content-translation-overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    $supported_types = array();
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($this->isSupported($entity_type)) {
        $supported_types[$entity_type] = $entity_info;
      }
    }
    return $supported_types;
  }

}
