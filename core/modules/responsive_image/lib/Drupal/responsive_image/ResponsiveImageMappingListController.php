<?php

/**
 * @file
 * Contains Drupal\responsive_image\ResponsiveImageListController.
 */

namespace Drupal\responsive_image;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of responsive image mappings.
 */
class ResponsiveImageMappingListController extends ConfigEntityListController {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['id'] = t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $operations['duplicate'] = array(
      'title' => t('Duplicate'),
      'weight' => 15,
    ) + $entity->urlInfo('duplicate-form');
    return $operations;
  }

}
