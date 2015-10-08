<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\Derivative\ContentTranslationContextualLinks.
 */

namespace Drupal\content_translation\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for content translation.
 *
 * @see \Drupal\content_translation\Plugin\Menu\ContextualLink\ContentTranslationContextualLinks
 */
class ContentTranslationContextualLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a new ContentTranslationContextualLinks.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   */
  public function __construct(ContentTranslationManagerInterface $content_translation_manager) {
    $this->contentTranslationManager = $content_translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('content_translation.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Create contextual links for translatable entity types.
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      $this->derivatives[$entity_type_id]['title'] = t('Translate');
      $this->derivatives[$entity_type_id]['route_name'] = "entity.$entity_type_id.content_translation_overview";
      $this->derivatives[$entity_type_id]['group'] = $entity_type_id;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
