<?php

/**
 * Definition of Drupal\views_ui_listing_test\ConfigTestListController.
 */

namespace Drupal\views_ui_listing_test;

use Drupal\views_ui_listing\EntityListControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Views config entity listing controller.
 */
class ConfigTestListController extends EntityListControllerBase {

  /**
   * Overrides Drupal\views_ui_listing\EntityListControllerBase::hookMenu();
   */
  public function hookMenu() {
    $path = $this->entityInfo['list path'];

    $items = parent::hookMenu();
    $items[$path]['title'] = 'Config test';
    $items[$path]['description'] = 'Config test listing page.';
    return $items;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::actionLinkMappings().
   */
  public function defineActionLinks(EntityInterface $entity) {
    $id = $entity->id();

    // @todo Add AJAX link to test.
    return array(
      'edit' => array(
        'title' => 'edit',
        'href' => "admin/structure/config_test/manage/$id/edit",
        'ajax' => FALSE,
      ),
      'add' => array(
        'title' => 'add',
        'href' => "admin/structure/config_test/add",
        'ajax' => FALSE,
      ),
      'delete' => array(
        'title' => 'delete',
        'href' => "admin/structure/config_test/manage/$id/delete",
        'ajax' => FALSE,
      ),
    );
  }

}
