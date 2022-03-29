<?php

namespace Drupal\help\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Core\Extension\ModuleExtensionList;
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
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Creates a new HelpController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\help\HelpSectionManager $help_manager
   *   The help section manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList|null $module_extension_list
   *   The module extension list.
   */
  public function __construct(RouteMatchInterface $route_match, HelpSectionManager $help_manager, ModuleExtensionList $module_extension_list) {
    $this->routeMatch = $route_match;
    $this->helpManager = $help_manager;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('plugin.manager.help_section'),
      $container->get('extension.list.module')
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
      if (!empty($plugin_definition['permission']) && !$this->currentUser()->hasPermission($plugin_definition['permission'])) {
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
        '#weight' => $plugin_definition['weight'],
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
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function helpPage($name) {
    $build = [];
    if ($this->moduleHandler()->hasImplementations('help', $name)) {
      $module_name = $this->moduleHandler()->getName($name);
      $build['#title'] = $module_name;

      $info = $this->moduleExtensionList->getExtensionInfo($name);
      if ($info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::EXPERIMENTAL) {
        $this->messenger()->addWarning($this->t('This module is experimental. <a href=":url">Experimental modules</a> are provided for testing purposes only. Use at your own risk.', [':url' => 'https://www.drupal.org/core/experimental']));
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
      $admin_tasks = system_get_module_admin_tasks($name, $info);
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
