<?php

/**
 * @file
 * Definition of Drupal\views_ui\ViewListController.
 */

namespace Drupal\views_ui;

use Drupal\Core\Entity\EntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Views.
 */
class ViewListController extends EntityListController {

  /**
   * Overrides Drupal\Core\Entity\EntityListController::load();
   */
  public function load() {
    $entities = array(
      'enabled' => array(),
      'disabled' => array(),
    );
    foreach (parent::load() as $entity) {
      if ($entity->isEnabled()) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow();
   */
  public function buildRow(EntityInterface $view) {
    return array(
      'data' => array(
        'view_name' => theme('views_ui_view_info', array('view' => $view)),
        'description' => $view->get('description'),
        'tag' => $view->get('tag'),
        'path' => implode(', ', $view->getPaths()),
        'operations' => array(
          'data' => $this->buildOperations($view),
        ),
      ),
      'title' => t('Machine name: ') . $view->id(),
      'class' => array($view->isEnabled() ? 'views-ui-list-enabled' : 'views-ui-list-disabled'),
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildHeader();
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
   * Implements Drupal\Core\Entity\EntityListController::getOperations();
   */
  public function getOperations(EntityInterface $view) {
    $uri = $view->uri();
    $path = $uri['path'];

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
    $definition['clone'] = array(
      'title' => t('Clone'),
      'href' => "$path/clone",
      'weight' => 15,
    );

    return $definition;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildOperations();
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);

    // Allow operations to specify that they use AJAX.
    foreach ($build['#links'] as &$operation) {
      if (!empty($operation['ajax'])) {
        $operation['attributes']['class'][] = 'use-ajax';
      }
    }

    // Use the dropbutton #type.
    unset($build['#theme']);
    $build['#type'] = 'dropbutton';

    return $build;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::render();
   */
  public function render() {
    $entities = $this->load();
    $list['#type'] = 'container';
    $list['#attached']['css'] = ViewFormControllerBase::getAdminCSS();
    $list['#attached']['library'][] = array('system', 'drupal.ajax');
    $list['#attributes']['id'] = 'views-entity-list';
    $list['enabled']['heading']['#markup'] = '<h2>' . t('Enabled') . '</h2>';
    $list['disabled']['heading']['#markup'] = '<h2>' . t('Disabled') . '</h2>';
    foreach (array('enabled', 'disabled') as $status) {
      $list[$status]['#type'] = 'container';
      $list[$status]['#attributes'] = array('class' => array('views-list-section', $status));
      $list[$status]['table'] = array(
        '#theme' => 'table',
        '#header' => $this->buildHeader(),
        '#rows' => array(),
      );
      foreach ($entities[$status] as $entity) {
        $list[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    // @todo Use a placeholder for the entity label if this is abstracted to
    // other entity types.
    $list['enabled']['table']['#empty'] = t('There are no enabled views.');
    $list['disabled']['table']['#empty'] = t('There are no disabled views.');

    return $list;
  }

}
