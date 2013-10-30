<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\Derivative\ContentTranslationLocalTasks.
 */

namespace Drupal\content_translation\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The base plugin ID
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Constructs a new ContentTranslationLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct($base_plugin_id, EntityManagerInterface $entity_manager, RouteProviderInterface $route_provider) {
    $this->entityManager = $entity_manager;
    $this->routeProvider = $route_provider;
    $this->basePluginId = $base_plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
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
        // Find the route name for the translation overview.
        $translation_route_name = "content_translation.translation_overview_$entity_type";
        $translation_tab = $translation_route_name;

        $this->derivatives[$translation_tab] = $base_plugin_definition + array(
          'entity_type' => $entity_type,
        );
        $this->derivatives[$translation_tab]['title'] = 'Translate';
        $this->derivatives[$translation_tab]['route_name'] = $translation_route_name;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

  /**
   * Alters the local tasks to find the proper tab_root_id for each task.
   */
  public function alterLocalTasks(array &$local_tasks) {
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info['translatable'] && isset($entity_info['translation'])) {
        $path = '/' . preg_replace('/%(.*)/', '{$1}', $entity_info['menu_base_path']);
        if ($routes = $this->routeProvider->getRoutesByPattern($path)->all()) {
          // Find the route name for the entity page.
          $entity_route_name = key($routes);

          // Find the route name for the translation overview.
          $translation_route_name = "content_translation.translation_overview_$entity_type";
          $translation_tab = $this->basePluginId . ':' . $translation_route_name;

          $local_tasks[$translation_tab]['tab_root_id'] = $this->getTaskFromRoute($entity_route_name, $local_tasks);
        }
      }
    }
  }

  /**
   * Find the local task ID of the parent route given the route name.
   *
   * @param string $route_name
   *   The route name of the parent local task.
   * @param array $local_tasks
   *   An array of all local task definitions.
   *
   * @return bool|string
   *   Returns the local task ID of the parent task, otherwise return FALSE.
   */
  protected function getTaskFromRoute($route_name, &$local_tasks) {
    $parent_local_task = FALSE;
    foreach ($local_tasks as $plugin_id => $local_task) {
      if ($local_task['route_name'] == $route_name) {
        $parent_local_task = $plugin_id;
        break;
      }
    }

    return $parent_local_task;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   *
   * @todo Move to derivative base. https://drupal.org/node/2112575
   */
  public function t($string, array $args = array(), array $options = array()) {
    \Drupal::translation()->translate($string, $args, $options);
  }
}
