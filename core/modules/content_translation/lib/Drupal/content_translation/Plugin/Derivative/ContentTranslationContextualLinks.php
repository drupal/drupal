<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\Derivative\ContentTranslationContextualLinks.
 */

namespace Drupal\content_translation\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for content translation.
 *
 * @see \Drupal\content_translation\Plugin\Menu\ContextualLink\ContentTranslationContextualLinks
 */
class ContentTranslationContextualLinks extends DerivativeBase implements ContainerDerivativeInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new ContentTranslationContextualLinks.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Create contextual links for all possible entity types.
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info->isTranslatable()) {
        $this->derivatives[$entity_type]['title'] = t('Translate');
        $this->derivatives[$entity_type]['route_name'] = $entity_info->getLinkTemplate('drupal:content-translation-overview');
        $this->derivatives[$entity_type]['group'] = $entity_type;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
