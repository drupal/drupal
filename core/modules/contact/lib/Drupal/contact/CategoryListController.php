<?php

/**
 * Definition of Drupal\contact\CategoryListController.
 */

namespace Drupal\contact;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of contact categories.
 */
class CategoryListController extends ConfigEntityListController {

  /**
   * Overrides Drupal\Core\Entity\EntityListController::getOperations().
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if ($this->moduleHandler->moduleExists('field_ui')) {
      $uri = $entity->uri();
      $operations['manage-fields'] = array(
        'title' => t('Manage fields'),
        'href' => $uri['path'] . '/fields',
        'options' => $uri['options'],
        'weight' => 11,
      );
      $operations['manage-form-display'] = array(
        'title' => t('Manage form display'),
        'href' => $uri['path'] . '/form-display',
        'options' => $uri['options'],
        'weight' => 12,
      );
      $operations['manage-display'] = array(
        'title' => t('Manage display'),
        'href' => $uri['path'] . '/display',
        'options' => $uri['options'],
        'weight' => 13,
      );
    }
    return $operations;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['category'] = t('Category');
    $header['recipients'] = t('Recipients');
    $header['selected'] = t('Selected');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $row['category'] = $this->getLabel($entity);
    // Special case the personal category.
    if ($entity->id() == 'personal') {
      $row['recipients'] = t('Selected user');
      $row['selected'] = t('No');
    }
    else {
      $row['recipients'] = check_plain(implode(', ', $entity->recipients));
      $default_category = \Drupal::config('contact.settings')->get('default_category');
      $row['selected'] = ($default_category == $entity->id() ? t('Yes') : t('No'));
    }
    return $row + parent::buildRow($entity);
  }

}
