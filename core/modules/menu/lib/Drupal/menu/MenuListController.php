<?php

/**
 * Contains \Drupal\menu\MenuListController.
 */

namespace Drupal\menu;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of contact categories.
 */
class MenuListController extends ConfigEntityListController {

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $row['title'] = t('Title');
    $row['description'] = array(
      'data' => t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    $row['operations'] = t('Operations');
    return $row;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = array(
      'data' => check_plain($entity->label()),
      'class' => array('menu-label'),
    );
    $row['description'] = filter_xss_admin($entity->description);
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::getOperations();
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();

    $operations['edit']['title'] = t('Edit menu');
    $operatuins['edit']['href'] = $uri['path'];
    $operations['add'] = array(
      'title' => t('Add link'),
      'href' => $uri['path'] . '/add',
      'options' => $uri['options'],
      'weight' => 20,
    );
    // System menus could not be deleted.
    $system_menus = menu_list_system_menus();
    if (isset($system_menus[$entity->id()])) {
      unset($operations['delete']);
    }
    else {
      $operations['delete']['title'] = t('Delete menu');
    }
    return $operations;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::render();
   */
  public function render() {
    $build = parent::render();
    $build['#attached']['css'][] = drupal_get_path('module', 'menu') . '/css/menu.admin.css';
    return $build;
  }

}
