<?php

/**
 * @file
 * Contains Drupal\picture\PictureListController.
 */

namespace Drupal\picture;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Pictures.
 */
class PictureMappingListController extends ConfigEntityListController {

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
    $uri = $entity->uri();
    $operations['duplicate'] = array(
      'title' => t('Duplicate'),
      'href' => $uri['path'] . '/duplicate',
      'options' => $uri['options'],
      'weight' => 15,
    );
    return $operations;
  }

}
