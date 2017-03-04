<?php

namespace Drupal\help\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\help\HelpSectionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for help routes.
 */
class HelpController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The help section plugin manager.
   *
   * @var \Drupal\help\HelpSectionManager
   */
  protected $helpManager;

  /**
   * Creates a new HelpController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\help\HelpSectionManager $help_manager
   *   The help section manager.
   */
  public function __construct(RouteMatchInterface $route_match, HelpSectionManager $help_manager) {
    $this->routeMatch = $route_match;
    $this->helpManager = $help_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('plugin.manager.help_section')
    );
  }

  /**
   * Prints a page listing various types of help.
   *
   * The page has sections defined by \Drupal\help\HelpSectionPluginInterface
   * plugins.
   *
   * @return array
   *   A render array for the help page.
   */
  public function helpMain() {
    $output = [];

    // We are checking permissions, so add the user.permissions cache context.
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts(['user.permissions']);

    $plugins = $this->helpManager->getDefinitions();
    $cacheability->addCacheableDependency($this->helpManager);

    foreach ($plugins as $plugin_id => $plugin_definition) {
      // Check the provided permission.
      if (!empty($plugin_definition['permission']) && !$this->currentuser()->hasPermission($plugin_definition['permission'])) {
        continue;
      }

      // Add the section to the page.
      /** @var \Drupal\help\HelpSectionPluginInterface $plugin */
      $plugin = $this->helpManager->createInstance($plugin_id);
      $this_output = [
        '#theme' => 'help_section',
        '#title' => $plugin->getTitle(),
        '#description' => $plugin->getDescription(),
        '#empty' => $this->t('There is currently nothing in this section.'),
        '#links' => [],
      ];

      $links = $plugin->listTopics();
      if (is_array($links) && count($links)) {
        $this_output['#links'] = $links;
      }

      $cacheability->addCacheableDependency($plugin);
      $output[$plugin_id] = $this_output;
    }

    $cacheability->applyTo($output);
    return $output;
  }

  /**
   * Prints a page listing general help for a module.
   *
   * @param string $name
   *   A module name to display a help page for.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function helpPage($name) {
    $build = [];
    if ($this->moduleHandler()->implementsHook($name, 'help')) {
      $module_name = $this->moduleHandler()->getName($name);
      $build['#title'] = $module_name;

      $info = system_get_info('module', $name);
      if ($info['package'] === 'Core (Experimental)') {
        drupal_set_message($this->t('This module is experimental. <a href=":url">Experimental modules</a> are provided for testing purposes only. Use at your own risk.', [':url' => 'https://www.drupal.org/core/experimental']), 'warning');
      }

      $temp = $this->moduleHandler()->invoke($name, 'help', ["help.page.$name", $this->routeMatch]);
      if (empty($temp)) {
        $build['top'] = ['#markup' => $this->t('No help is available for module %module.', ['%module' => $module_name])];
      }
      else {
        if (!is_array($temp)) {
          $temp = ['#markup' => $temp];
        }
        $build['top'] = $temp;
      }

      // Only print list of administration pages if the module in question has
      // any such pages associated with it.
      $admin_tasks = system_get_module_admin_tasks($name, system_get_info('module', $name));
      if (!empty($admin_tasks)) {
        $links = [];
        foreach ($admin_tasks as $task) {
          $link['url'] = $task['url'];
          $link['title'] = $task['title'];
          $links[] = $link;
        }
        $build['links'] = [
          '#theme' => 'links__help',
          '#heading' => [
            'level' => 'h3',
            'text' => $this->t('@module administration pages', ['@module' => $module_name]),
          ],
          '#links' => $links,
        ];
      }
      return $build;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
