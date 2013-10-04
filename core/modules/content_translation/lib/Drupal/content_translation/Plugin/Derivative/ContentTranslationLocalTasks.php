<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\Derivative\ContentTranslationLocalTasks.
 */

namespace Drupal\content_translation\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for content translation.
 */
class ContentTranslationLocalTasks extends DerivativeBase implements ContainerDerivativeInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a new ContentTranslationLocalTasks.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(EntityManager $entity_manager, RouteProviderInterface $route_provider) {
    $this->entityManager = $entity_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Create tabs for all possible entity types.
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info['translatable'] && isset($entity_info['translation'])) {
        $path = '/' . preg_replace('/%(.*)/', '{$1}', $entity_info['menu_base_path']);
        if ($routes = $this->routeProvider->getRoutesByPattern($path)->all()) {
          // Find the route name for the entity page.
          $entity_route_name = key($routes);
          $entity_tab = $entity_route_name . '_tab';
          // Find the route name for the translation overview.
          $translation_route_name = "content_translation.translation_overview_$entity_type";
          $translation_tab = $translation_route_name . '_tab';

          // Both tabs will have the same root and entity type.
          $common_tab_settings = array(
            'tab_root_id' => $entity_tab,
            'entity_type' => $entity_type,
          );
          $this->derivatives[$entity_tab] = $base_plugin_definition + $common_tab_settings;
          $this->derivatives[$entity_tab]['title'] = t('Edit');
          $this->derivatives[$entity_tab]['route_name'] = $entity_route_name;

          $this->derivatives[$translation_tab] = $base_plugin_definition + $common_tab_settings;
          $this->derivatives[$translation_tab]['title'] = t('Translate');
          $this->derivatives[$translation_tab]['route_name'] = $translation_route_name;
        }
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
