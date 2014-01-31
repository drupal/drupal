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
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }
    return $operations;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['type'] = t('Block type');
    $header['description'] = t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $uri = $entity->urlInfo();
    $row['type'] = \Drupal::l($entity->label(), $uri['route_name'], $uri['route_parameters'], $uri['options']);
    $row['description'] = filter_xss_admin($entity->description);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t('Custom block types');
  }

}
