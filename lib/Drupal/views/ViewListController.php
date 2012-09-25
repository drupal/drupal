<?php

/**
 * @file
 * Definition of Drupal\views\ViewListController.
 */

namespace Drupal\views;

use Drupal\views_ui_listing\EntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Views.
 */
class ViewListController extends EntityListController {

  /**
   * Overrides Drupal\views_ui_listing\EntityListController::load();
   */
  public function load() {
    $entities = parent::load();
    uasort($entities, function ($a, $b) {
      $a_enabled = $a->isEnabled();
      $b_enabled = $b->isEnabled();
      if ($a_enabled != $b_enabled) {
        return $a_enabled < $b_enabled;
      }
      return $a->id() > $b->id();
    });
    return $entities;
  }

  /**
   * Overrides Drupal\views_ui_listing\EntityListController::buildRow();
   */
  public function buildRow(EntityInterface $view) {
    $operations = $this->buildOperations($view);
    $operations['#theme'] = 'links__ctools_dropbutton';
    return array(
      'data' => array(
        'view_name' => theme('views_ui_view_info', array('view' => $view)),
        'description' => $view->description,
        'tag' => $view->tag,
        'path' => implode(', ', $view->getPaths()),
        'operations' => drupal_render($operations),
      ),
      'title' => t('Machine name: ') . $view->id(),
      'class' => array($view->isEnabled() ? 'views-ui-list-enabled' : 'views-ui-list-disabled'),
    );
  }

  /**
   * Overrides Drupal\views_ui_listing\EntityListController::buildHeader();
   */
  public function buildHeader() {
    return array(
      'view_name' => array(
        'data' => t('View name'),
        'class' => array('views-ui-name'),
      ),
      'description' => array(
        'data' => t('Description'),
        'class' => array('views-ui-description'),
      ),
      'tag' => array(
        'data' => t('Tag'),
        'class' => array('views-ui-tag'),
      ),
      'path' => array(
        'data' => t('Path'),
        'class' => array('views-ui-path'),
      ),
      'operations' => array(
        'data' => t('Operations'),
        'class' => array('views-ui-operations'),
      ),
    );
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListController::getOperations();
   */
  public function getOperations(EntityInterface $view) {
    $uri = $view->uri();
    $path = $uri['path'] . '/view/' . $view->id();

    $definition['edit'] = array(
      'title' => t('Edit'),
      'href' => "$path/edit",
      'weight' => -5,
    );
    if (!$view->isEnabled()) {
      $definition['enable'] = array(
        'title' => t('Enable'),
        'ajax' => TRUE,
        'token' => TRUE,
        'href' => "$path/enable",
        'weight' => -10,
      );
    }
    else {
      $definition['disable'] = array(
        'title' => t('Disable'),
        'ajax' => TRUE,
        'token' => TRUE,
        'href' => "$path/disable",
        'weight' => 0,
      );
    }
    // This property doesn't exist yet.
    if (!empty($view->overridden)) {
      $definition['revert'] = array(
        'title' => t('Revert'),
        'href' => "$path/revert",
        'weight' => 5,
      );
    }
    else {
      $definition['delete'] = array(
        'title' => t('Delete'),
        'href' => "$path/delete",
        'weight' => 10,
      );
    }
    return $definition;
  }

  /**
   * Overrides Drupal\views_ui_listing\EntityListController::render();
   */
  public function render() {
    $list = parent::render();
    $list['#attached']['css'] = views_ui_get_admin_css();
    return $list;
  }

}
