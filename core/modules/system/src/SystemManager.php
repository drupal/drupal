<?php
/**
 * @file
 * Contains \Drupal\system\SystemManager.
 */

namespace Drupal\system;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * System Manager Service.
 */
class SystemManager {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The menu link tree manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * A static cache of menu items.
   *
   * @var array
   */
  protected $menuItems;

  /**
   * Requirement severity -- Requirement successfully met.
   */
  const REQUIREMENT_OK = 0;

  /**
   * Requirement severity -- Warning condition; proceed but flag warning.
   */
  const REQUIREMENT_WARNING = 1;

  /**
   * Requirement severity -- Error condition; abort installation.
   */
  const REQUIREMENT_ERROR = 2;

  /**
   * Constructs a SystemManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree manager.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Connection $database, EntityManagerInterface $entity_manager, RequestStack $request_stack, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail) {
    $this->moduleHandler = $module_handler;
    $this->database = $database;
    $this->requestStack = $request_stack;
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * Checks for requirement severity.
   *
   * @return boolean
   *   Returns the status of the system.
   */
  public function checkRequirements() {
    $requirements = $this->listRequirements();
    return $this->getMaxSeverity($requirements) == static::REQUIREMENT_ERROR;
  }

  /**
   * Displays the site status report. Can also be used as a pure check.
   *
   * @return array
   *   An array of system requirements.
   */
  public function listRequirements() {
    // Load .install files
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();

    // Check run-time requirements and status information.
    $requirements = $this->moduleHandler->invokeAll('requirements', array('runtime'));
    usort($requirements, function($a, $b) {
      if (!isset($a['weight'])) {
        if (!isset($b['weight'])) {
          return strcmp($a['title'], $b['title']);
        }
        return -$b['weight'];
      }
      return isset($b['weight']) ? $a['weight'] - $b['weight'] : $a['weight'];
    });

    return $requirements;
  }

  /**
   * Extracts the highest severity from the requirements array.
   *
   * @param $requirements
   *   An array of requirements, in the same format as is returned by
   *   hook_requirements().
   *
   * @return
   *   The highest severity in the array.
   */
  public function getMaxSeverity(&$requirements) {
    $severity = static::REQUIREMENT_OK;
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity'])) {
        $severity = max($severity, $requirement['severity']);
      }
    }
    return $severity;
  }

  /**
   * Loads the contents of a menu block.
   *
   * This function is often a destination for these blocks.
   * For example, 'admin/structure/types' needs to have a destination to be
   * valid in the Drupal menu system, but too much information there might be
   * hidden, so we supply the contents of the block.
   *
   * @return array
   *   A render array suitable for drupal_render.
   */
  public function getBlockContents() {
    // We hard-code the menu name here since otherwise a link in the tools menu
    // or elsewhere could give us a blank block.
    $link = $this->menuActiveTrail->getActiveLink('admin');
    if ($link && $content = $this->getAdminBlock($link)) {
      $output = array(
        '#theme' => 'admin_block_content',
        '#content' => $content,
      );
    }
    else {
      $output = array(
        '#markup' => t('You do not have any administrative items.'),
      );
    }
    return $output;
  }

  /**
   * Provide a single block on the administration overview page.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $instance
   *   The menu item to be displayed.
   *
   * @return array
   *   An array of menu items, as expected by theme_admin_block_content().
   */
  public function getAdminBlock(MenuLinkInterface $instance) {
    $content = array();
    // Only find the children of this link.
    $link_id = $instance->getPluginId();
    $parameters = new MenuTreeParameters();
    $parameters->setRoot($link_id)->excludeRoot()->setTopLevelOnly()->onlyEnabledLinks();
    $tree = $this->menuTree->load(NULL, $parameters);
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $this->menuTree->transform($tree, $manipulators);
    foreach ($tree as $key => $element) {
      /** @var $link \Drupal\Core\Menu\MenuLinkInterface */
      $link = $element->link;
      $content[$key]['title'] = $link->getTitle();
      $content[$key]['options'] = $link->getOptions();
      $content[$key]['description'] = $link->getDescription();
      $content[$key]['url'] = $link->getUrlObject();
    }
    ksort($content);
    return $content;
  }

}
