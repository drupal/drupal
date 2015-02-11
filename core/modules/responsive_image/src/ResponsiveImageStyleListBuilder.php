<?php

/**
 * @file
 * Contains Drupal\responsive_image\ResponsiveImageStyleListBuilder.
 */

namespace Drupal\responsive_image;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of responsive image styles.
 */
class ResponsiveImageStyleListBuilder extends ConfigEntityListBuilder {

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
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['duplicate'] = array(
      'title' => t('Duplicate'),
      'weight' => 15,
      'url' => $entity->urlInfo('duplicate-form'),
    );
    return $operations;
  }

}
