<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockListController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListController;

/**
 * Provides a listing of custom block entities.
 */
class CustomBlockListController extends EntityListController {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    $header['label'] = t('Block description');
    unset($header['id']);
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    unset($row['id']);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    // The custom block edit path does not contain '/edit'.
    if (isset($operations['edit'])) {
      $uri = $entity->uri();
      $operations['edit']['href'] = $uri['path'];
      $operations['edit']['query']['destination'] = 'admin/structure/custom-blocks';
    }
    return $operations;
  }

}
