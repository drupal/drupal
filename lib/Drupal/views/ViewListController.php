<?php

/**
 * @file
 * Definition of Drupal\views\ViewListController.
 */

namespace Drupal\views;

use Drupal\views_ui_listing\EntityListControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Views.
 */
class ViewListController extends EntityListControllerBase {

  public function __construct($entity_type, $entity_info = FALSE) {
    parent::__construct($entity_type, $entity_info);
  }

  /**
   * Overrides Drupal\views_ui_listing\EntityListControllerBase::hookMenu();
   */
  public function hookMenu() {
    // Find the path and the number of path arguments.
    $path = $this->entityInfo['list path'];
    $path_count = count(explode('/', $path));

    $items = parent::hookMenu();
    // Override the access callback.
    // @todo Probably won't need to specify user access.
    $items[$path]['title'] = 'Views';
    $items[$path]['description'] = 'Manage customized lists of content.';
    $items[$path]['access callback'] = 'user_access';
    $items[$path]['access arguments'] = array('administer views');

    // Add a default local task, so we have tabs.
    $items["$path/list"] = array(
      'title' => 'List',
      'weight' => -10,
      'type' => MENU_DEFAULT_LOCAL_TASK,
    );

    // Set up the base for AJAX callbacks.
    $ajax_base = array(
      'page callback' => 'views_ui_listing_ajax_callback',
      'page arguments' => array($this, $path_count + 1, $path_count + 2),
      'access callback' => 'user_access',
      'access arguments' => array('administer views'),
      'type' => MENU_CALLBACK,
    );

    // Add an enable link.
    $items["$path/view/%views_ui/enable"] = array(
      'title' => 'Enable a view',
    ) + $ajax_base;
    // Add a disable link.
    $items["$path/view/%views_ui/disable"] = array(
      'title' => 'Disable a view',
    ) + $ajax_base;

    return $items;
  }

  /**
   * Overrides Drupal\views_ui_listing\EntityListControllerBase::getList();
   */
  public function getList() {
    $list = parent::getList();
    uasort($list, function ($a, $b) {
      $a_enabled = $a->isEnabled();
      $b_enabled = $b->isEnabled();
      if ($a_enabled != $b_enabled) {
        return $a_enabled < $b_enabled;
      }
      return $a->id() > $b->id();
    });
    return $list;
  }

  /**
   * Overrides Drupal\views_ui_listing\EntityListControllerBase::getRowData();
   */
  public function getRowData(EntityInterface $view) {
    $operations = $this->buildActionLinks($view);
    $operations['#theme'] = 'links__ctools_dropbutton';
    return array(
      'data' => array(
        'view_name' => theme('views_ui_view_info', array('view' => $view)),
        'description' => $view->description,
        'tag' => $view->tag,
        'path' => implode(', ', $view->getPaths()),
        'operations' => drupal_render($operations),
      ),
      'title' => t('Machine name: ') . $view->id(),
      'class' => array($view->isEnabled() ? 'views-ui-list-enabled' : 'views-ui-list-disabled'),
    );
  }

  /**
   * Overrides Drupal\views_ui_listing\EntityListControllerBase::getRowData();
   */
  public function getHeaderData() {
    return array(
      'view_name' => array(
        'data' => t('View name'),
        'class' => array('views-ui-name'),
      ),
      'description' => array(
        'data' => t('Description'),
        'class' => array('views-ui-description'),
      ),
      'tag' => array(
        'data' => t('Tag'),
        'class' => array('views-ui-tag'),
      ),
      'path' => array(
        'data' => t('Path'),
        'class' => array('views-ui-path'),
      ),
      'actions' => array(
        'data' => t('Operations'),
        'class' => array('views-ui-operations'),
      ),
    );
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::defineActionLinks();
   */
  public function defineActionLinks(EntityInterface $view) {
    $path = $this->entityInfo['list path'] . '/view/' . $view->id();
    $enabled = $view->isEnabled();

    if (!$enabled) {
      $definition['enable'] = array(
        'title' => t('Enable'),
        'ajax' => TRUE,
        'token' => TRUE,
        'href' => "$path/enable",
      );
    }
    $definition['edit'] = array(
      'title' => t('Edit'),
      'href' => "$path/edit",
    );
    if ($enabled) {
      $definition['disable'] = array(
        'title' => t('Disable'),
        'ajax' => TRUE,
        'token' => TRUE,
        'href' => "$path/disable",
      );
    }
    // This property doesn't exist yet.
    if (!empty($view->overridden)) {
      $definition['revert'] = array(
        'title' => t('Revert'),
        'href' => "$path/revert",
      );
    }
    else {
      $definition['delete'] = array(
        'title' => t('Delete'),
        'href' => "$path/delete",
      );
    }
    return $definition;
  }

  /**
   * Overrides Drupal\views_ui_listing\EntityListControllerBase::renderList();
   */
  public function renderList() {
    $list = parent::renderList();
    $list['#attached']['css'] = views_ui_get_admin_css();
    return $list;
  }

}
