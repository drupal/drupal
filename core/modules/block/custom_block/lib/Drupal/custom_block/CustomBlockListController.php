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
    $header['label'] = t('Block description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if (isset($operations['edit'])) {
      $operations['edit']['query']['destination'] = 'admin/structure/block/custom-blocks';
    }
    return $operations;
  }

}
