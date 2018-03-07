<?php

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of image style entities.
 *
 * @see \Drupal\image\Entity\ImageStyle
 */
class ImageStyleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Style name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $flush = [
      'title' => t('Flush'),
      'weight' => 200,
      'url' => $entity->urlInfo('flush-form'),
    ];

    $operations = parent::getDefaultOperations($entity) + [
      'flush' => $flush,
    ];

    // Remove destination URL from the edit link to allow editing image
    // effects.
    if (isset($operations['edit'])) {
      $operations['edit']['url'] = $entity->toUrl('edit-form');
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are currently no styles. <a href=":url">Add a new one</a>.', [
      ':url' => Url::fromRoute('image.style_add')->toString(),
    ]);
    return $build;
  }

}
