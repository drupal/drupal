<?php

/**
 * @file
 * Contains \Drupal\contact\CategoryListBuilder.
 */

namespace Drupal\contact;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of contact category entities.
 *
 * @see \Drupal\contact\Entity\Category
 */
class CategoryListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['category'] = t('Category');
    $header['recipients'] = t('Recipients');
    $header['selected'] = t('Selected');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['category'] = $this->getLabel($entity);
    // Special case the personal category.
    if ($entity->id() == 'personal') {
      $row['recipients'] = t('Selected user');
      $row['selected'] = t('No');
    }
    else {
      $row['recipients'] = String::checkPlain(implode(', ', $entity->recipients));
      $default_category = \Drupal::config('contact.settings')->get('default_category');
      $row['selected'] = ($default_category == $entity->id() ? t('Yes') : t('No'));
    }
    return $row + parent::buildRow($entity);
  }

}
