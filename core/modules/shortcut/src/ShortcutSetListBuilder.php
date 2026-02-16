<?php

namespace Drupal\shortcut;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of shortcut set entities.
 *
 * @see \Drupal\shortcut\Entity\ShortcutSet
 */
class ShortcutSetListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity/* , ?CacheableMetadata $cacheability = NULL */) {
    $args = func_get_args();
    $cacheability = $args[1] ?? new CacheableMetadata();
    $operations = parent::getDefaultOperations($entity, $cacheability);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Edit shortcut set');
    }

    $operations['list'] = [
      'title' => $this->t('List links'),
      'url' => $entity->toUrl('customize-form'),
    ];
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

}
