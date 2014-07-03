<?php

/**
 * @file
 * Contains \Drupal\help\Controller\HelpController.
 */

namespace Drupal\help\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Component\Utility\String;

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
   * Creates a new HelpController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * Prints a page listing a glossary of Drupal terminology.
   *
   * @return string
   *   An HTML string representing the contents of help page.
   */
  public function helpMain() {
    $output = array(
      '#attached' => array(
        'css' => array(drupal_get_path('module', 'help') . '/css/help.module.css'),
      ),
      '#markup' => '<h2>' . $this->t('Help topics') . '</h2><p>' . $this->t('Help is available on the following items:') . '</p>' . $this->helpLinksAsList(),
    );
    return $output;
  }

  /**
   * Provides a formatted list of available help topics.
   *
   * @return string
   *   A string containing the formatted list.
   */
  protected function helpLinksAsList() {
    $module_info = system_rebuild_module_data();

    $modules = array();
    foreach ($this->moduleHandler()->getImplementations('help') as $module) {
      if ($this->moduleHandler()->invoke($module, 'help', array("help.page.$module", $this->routeMatch))) {
        $modules[$module] = $module_info[$module]->info['name'];
      }
    }
    asort($modules);

    // Output pretty four-column list.
    $count = count($modules);
    $break = ceil($count / 4);
    $output = '<div class="clearfix"><div class="help-items"><ul>';
    $i = 0;
    foreach ($modules as $module => $name) {
      $output .= '<li>' . $this->l($name, 'help.page',  array('name' => $module)) . '</li>';
      if (($i + 1) % $break == 0 && ($i + 1) != $count) {
        $output .= '</ul></div><div class="help-items' . ($i + 1 == $break * 3 ? ' help-items-last' : '') . '"><ul>';
      }
      $i++;
    }
    $output .= '</ul></div></div>';

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
    $build = array();
    if ($this->moduleHandler()->implementsHook($name, 'help')) {
      $info = system_get_info('module');
      $build['#title'] = String::checkPlain($info[$name]['name']);

      $temp = $this->moduleHandler()->invoke($name, 'help', array("help.page.$name", $this->routeMatch));
      if (empty($temp)) {
        $build['top']['#markup'] = $this->t('No help is available for module %module.', array('%module' => $info[$name]['name']));
      }
      else {
        $build['top']['#markup'] = $temp;
      }

      // Only print list of administration pages if the module in question has
      // any such pages associated to it.
      $admin_tasks = system_get_module_admin_tasks($name, $info[$name]);
      if (!empty($admin_tasks)) {
        $links = array();
        foreach ($admin_tasks as $task) {
          $link = $task['url']->toArray();
          $link['title'] = $task['title'];
          $links[] = $link;
        }
        $build['links']['#links'] = array(
          '#heading' => array(
            'level' => 'h3',
            'text' => $this->t('@module administration pages', array('@module' => $info[$name]['name'])),
          ),
          '#links' => $links,
        );
      }
      return $build;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
