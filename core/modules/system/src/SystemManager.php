<?php
/**
 * @file
 * Contains \Drupal\system\SystemManager.
 */

namespace Drupal\system;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * The menu link storage.
   *
   * @var \Drupal\menu_link\MenuLinkStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   */
  public function __construct(ModuleHandlerInterface $module_handler, Connection $database, EntityManagerInterface $entity_manager, RequestStack $request_stack) {
    $this->moduleHandler = $module_handler;
    $this->database = $database;
    $this->menuLinkStorage = $entity_manager->getStorage('menu_link');
    $this->requestStack = $request_stack;
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
   * Fixes anonymous user on MySQL.
   *
   * MySQL import might have set the uid of the anonymous user to autoincrement
   * value. Let's try fixing it. See http://drupal.org/node/204411
   */
  public function fixAnonymousUid() {
    $this->database->update('users')
      ->expression('uid', 'uid - uid')
      ->condition('name', '')
      ->condition('pass', '')
      ->condition('status', 0)
      ->execute();
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
    $request = $this->requestStack->getCurrentRequest();
    $route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME);
    $items = $this->menuLinkStorage->loadByProperties(array('route_name' => $route_name));
    $item = reset($items);
    if ($content = $this->getAdminBlock($item)) {
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
   * @param \Drupal\menu_link\MenuLinkInterface|array $item
   *   The menu item to be displayed.
   *
   * @return array
   *   An array of menu items, as expected by theme_admin_block_content().
   */
  public function getAdminBlock($item) {
    if (!isset($item['mlid'])) {
      $menu_links = $this->menuLinkStorage->loadByProperties(array('link_path' => $item['path'], 'module' => 'system'));
      if ($menu_links) {
        $menu_link = reset($menu_links);
        $item['mlid'] = $menu_link->id();
        $item['menu_name'] = $menu_link->menu_name;
      }
      else {
        return array();
      }
    }

    if (isset($this->menuItems[$item['mlid']])) {
      return $this->menuItems[$item['mlid']];
    }

    $content = array();
    $menu_links = $this->menuLinkStorage->loadByProperties(array('plid' => $item['mlid'], 'menu_name' => $item['menu_name'], 'hidden' => 0));
    foreach ($menu_links as $link) {
      _menu_link_translate($link);
      if ($link['access']) {
        // The link description, either derived from 'description' in
        // hook_menu() or customized via Menu UI module is used as title attribute.
        if (!empty($link['localized_options']['attributes']['title'])) {
          $link['description'] = $link['localized_options']['attributes']['title'];
          unset($link['localized_options']['attributes']['title']);
        }
        // Prepare for sorting as in function _menu_tree_check_access().
        // The weight is offset so it is always positive, with a uniform 5-digits.
        $key = (50000 + $link['weight']) . ' ' . Unicode::strtolower($link['title']) . ' ' . $link['mlid'];
        $content[$key] = $link;
      }
    }
    ksort($content);
    $this->menuItems[$item['mlid']] = $content;
    return $content;
  }

}
