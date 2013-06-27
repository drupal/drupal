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
   * Overrides Drupal\config\EntityListControllerBase::hookMenu().
   */
  public function hookMenu() {
    $path = $this->entityInfo['list path'];
    $items = parent::hookMenu();

    // Override the access callback.
    $items[$path]['title'] = 'Picture Mappings';
    $items[$path]['description'] = 'Manage list of picture mappings.';
    $items[$path]['access callback'] = 'user_access';
    $items[$path]['access arguments'] = array('administer pictures');

    return $items;
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
