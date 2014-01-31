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
    $header['title'] = t('Title');
    $header['description'] = array(
      'data' => t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    return $header + parent::buildHeader();
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = array(
      'data' => $this->getLabel($entity),
      'class' => array('menu-label'),
    );
    $row['description'] = filter_xss_admin($entity->description);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit menu');
      $operations['add'] = array(
        'title' => t('Add link'),
        'weight' => 20,
      ) + $entity->urlInfo('add-form');
    }
    if (isset($operations['delete'])) {
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
