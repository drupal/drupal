<?php

/**
 * Contains \Drupal\menu\MenuListController.
 */

namespace Drupal\menu;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of menus.
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
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit menu');
    }
    if (isset($operations['delete'])) {
      $operations['delete']['title'] = t('Delete menu');
    }
    $operations['add'] = array(
      'title' => t('Add link'),
      'href' => $uri['path'] . '/add',
      'options' => $uri['options'],
      'weight' => 20,
    );
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
