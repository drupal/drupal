<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\Derivative\ContentTranslationLocalTasks.
 */

namespace Drupal\content_translation\Plugin\Derivative;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for content translation.
 */
class ContentTranslationLocalTasks extends DerivativeBase implements ContainerDerivativeInterface {

  /**
   * The base plugin ID
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a new ContentTranslationLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   */
  public function __construct($base_plugin_id, ContentTranslationManagerInterface $content_translation_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->contentTranslationManager = $content_translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('content_translation.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Create tabs for all possible entity types.
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      // Find the route name for the translation overview.
      $translation_route_name = $entity_type->getLinkTemplate('drupal:content-translation-overview');

      $this->derivatives[$translation_route_name] = array(
        'entity_type' => $entity_type_id,
        'title' => 'Translate',
        'route_name' => $translation_route_name,
        'base_route' => $entity_type->getLinkTemplate('canonical'),
      ) + $base_plugin_definition;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
