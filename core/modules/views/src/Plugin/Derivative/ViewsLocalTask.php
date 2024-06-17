<?php

namespace Drupal\views\Plugin\Derivative;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all views configured as local tasks.
 */
class ViewsLocalTask extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * Constructs a \Drupal\views\Plugin\Derivative\ViewsLocalTask instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view storage.
   */
  public function __construct(RouteProviderInterface $route_provider, StateInterface $state, EntityStorageInterface $view_storage) {
    $this->routeProvider = $route_provider;
    $this->state = $state;
    $this->viewStorage = $view_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('state'),
      $container->get('entity_type.manager')->getStorage('view')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $view_route_names = $this->state->get('views.view_route_names');
    foreach ($this->getApplicableMenuViews() as $pair) {
      /** @var \Drupal\views\ViewExecutable $executable */
      [$view_id, $display_id] = $pair;
      $executable = $this->viewStorage->load($view_id)->getExecutable();

      $executable->setDisplay($display_id);
      $menu = $executable->display_handler->getOption('menu');
      if (in_array($menu['type'], ['tab', 'default tab'])) {
        $plugin_id = 'view.' . $executable->storage->id() . '.' . $display_id;
        $route_name = $view_route_names[$executable->storage->id() . '.' . $display_id];

        // Don't add a local task for views which override existing routes.
        // @todo Alternative it could just change the existing entry.
        if ($route_name != $plugin_id) {
          continue;
        }

        $this->derivatives[$plugin_id] = [
          'route_name' => $route_name,
          'weight' => $menu['weight'],
          'title' => $menu['title'],
        ] + $base_plugin_definition;

        // Default local tasks have themselves as root tab.
        if ($menu['type'] == 'default tab') {
          $this->derivatives[$plugin_id]['base_route'] = $route_name;
        }
      }
    }
    return $this->derivatives;
  }

  /**
   * Alters base_route and parent_id into the views local tasks.
   */
  public function alterLocalTasks(&$local_tasks) {
    $view_route_names = $this->state->get('views.view_route_names');

    foreach ($this->getApplicableMenuViews() as $pair) {
      [$view_id, $display_id] = $pair;
      /** @var \Drupal\views\ViewExecutable $executable */
      $executable = $this->viewStorage->load($view_id)->getExecutable();

      $executable->setDisplay($display_id);
      $menu = $executable->display_handler->getOption('menu');

      // We already have set the base_route for default tabs.
      if (in_array($menu['type'], ['tab'])) {
        $plugin_id = 'view.' . $executable->storage->id() . '.' . $display_id;
        $view_route_name = $view_route_names[$executable->storage->id() . '.' . $display_id];

        // Don't add a local task for views which override existing routes.
        if ($view_route_name != $plugin_id) {
          unset($local_tasks[$plugin_id]);
          continue;
        }

        // Find out the parent route.
        // @todo Find out how to find both the root and parent tab.
        $path = $executable->display_handler->getPath();
        $split = explode('/', $path);
        array_pop($split);
        $path = implode('/', $split);

        $pattern = '/' . str_replace('%', '{}', $path);
        if ($routes = $this->routeProvider->getRoutesByPattern($pattern)) {
          foreach ($routes->all() as $name => $route) {
            $local_tasks['views_view:' . $plugin_id]['base_route'] = $name;
            // Skip after the first found route.
            break;
          }
        }
      }
    }
  }

  /**
   * Return a list of all views and display IDs that have a menu entry.
   *
   * @return array
   *   A list of arrays containing the $view and $display_id.
   *
   * @code
   * [
   *   [$view, $display_id],
   *   [$view, $display_id],
   * ];
   * @endcode
   */
  protected function getApplicableMenuViews() {
    return Views::getApplicableViews('uses_menu_links');
  }

}
