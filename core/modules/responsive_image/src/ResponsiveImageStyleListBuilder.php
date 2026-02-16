<?php

namespace Drupal\responsive_image;

use Drupal\Core\Cache\CacheableMetadata;
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
    $header['label'] = $this->t('Label');
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
  protected function getDefaultOperations(EntityInterface $entity/* , ?CacheableMetadata $cacheability = NULL */) {
    $args = func_get_args();
    $cacheability = $args[1] ?? new CacheableMetadata();
    $operations = parent::getDefaultOperations($entity, $cacheability);
    $operations['duplicate'] = [
      'title' => $this->t('Duplicate'),
      'weight' => 15,
      'url' => $entity->toUrl('duplicate-form'),
    ];
    return $operations;
  }

}
