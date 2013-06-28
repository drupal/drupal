<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockTypeListController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of custom block types.
 */
class CustomBlockTypeListController extends ConfigEntityListController {

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if (module_exists('field_ui')) {
      $uri = $entity->uri();
      $operations['manage-fields'] = array(
        'title' => t('Manage fields'),
        'href' => $uri['path'] . '/fields',
        'options' => $uri['options'],
        'weight' => 15,
      );
      $operations['manage-form-display'] = array(
        'title' => t('Manage form display'),
        'href' => $uri['path'] . '/form-display',
        'options' => $uri['options'],
        'weight' => 20,
      );
      $operations['manage-display'] = array(
        'title' => t('Manage display'),
        'href' => $uri['path'] . '/display',
        'options' => $uri['options'],
        'weight' => 25,
      );
    }
    return $operations;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $row['type'] = t('Block type');
    $row['description'] = t('Description');
    $row['operations'] = t('Operations');
    return $row;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    parent::buildRow($entity);
    $uri = $entity->uri();
    $row['type'] = l($entity->label(), $uri['path'], $uri['options']);
    $row['description'] = filter_xss_admin($entity->description);
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

}
