<?php

/**
 * @file
 * Contains \Drupal\views_ui\ViewListController.
 */

namespace Drupal\views_ui;

use Drupal\Component\Utility\String;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Views.
 */
class ViewListController extends ConfigEntityListController implements EntityControllerInterface {

  /**
   * The views display plugin manager to use.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $displayManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $container->get('plugin.manager.entity')->getStorageController($entity_type),
      $entity_info,
      $container->get('plugin.manager.views.display'),
      $container->get('module_handler')
    );
  }

  /**
   * Constructs a new EntityListController object.
   *
   * @param string $entity_type.
   *   The type of entity to be listed.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage.
   *   The entity storage controller class.
   * @param array $entity_info
   *   An array of entity info for this entity type.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $display_manager
   *   The views display plugin manager to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($entity_type, EntityStorageControllerInterface $storage, $entity_info, PluginManagerInterface $display_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type, $entity_info, $storage, $module_handler);

    $this->displayManager = $display_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array(
      'enabled' => array(),
      'disabled' => array(),
    );
    foreach (parent::load() as $entity) {
      if ($entity->status()) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $view) {
    return array(
      'data' => array(
        'view_name' => array(
          'data' => array(
            '#theme' => 'views_ui_view_info',
            '#view' => $view,
            '#displays' => $this->getDisplaysList($view)
          ),
        ),
        'description' => $view->get('description'),
        'tag' => $view->get('tag'),
        'path' => implode(', ', $this->getDisplayPaths($view)),
        'operations' => array(
          'data' => $this->buildOperations($view),
        ),
      ),
      'title' => t('Machine name: @name', array('@name' => $view->id())),
      'class' => array($view->status() ? 'views-ui-list-enabled' : 'views-ui-list-disabled'),
    );
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();

    $operations['clone'] = array(
      'title' => t('Clone'),
      'href' => $uri['path'] . '/clone',
      'options' => $uri['options'],
      'weight' => 15,
    );

    // Add AJAX functionality to enable/disable operations.
    foreach (array('enable', 'disable') as $op) {
      if (isset($operations[$op])) {
        $operations[$op]['ajax'] = TRUE;
        $operations[$op]['query']['token'] = drupal_get_token($op);
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);

    // Allow operations to specify that they use AJAX.
    foreach ($build['#links'] as &$operation) {
      if (!empty($operation['ajax'])) {
        $operation['attributes']['class'][] = 'use-ajax';
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
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

  /**
   * Gets a list of displays included in the view.
   *
   * @param \Drupal\Core\Entity\EntityInterface $view
   *   The view entity instance to get a list of displays for.
   *
   * @return array
   *   An array of display types that this view includes.
   */
  protected function getDisplaysList(EntityInterface $view) {
    $displays = array();
    foreach ($view->get('display') as $display) {
      $definition = $this->displayManager->getDefinition($display['display_plugin']);
      if (!empty($definition['admin'])) {
        $displays[$definition['admin']] = TRUE;
      }
    }

    ksort($displays);
    return array_keys($displays);
  }

  /**
   * Gets a list of paths assigned to the view.
   *
   * @param \Drupal\Core\Entity\EntityInterface $view
   *   The view entity.
   *
   * @return array
   *   An array of paths for this view.
   */
  protected function getDisplayPaths(EntityInterface $view) {
    $all_paths = array();
    $executable = $view->getExecutable();
    $executable->initDisplay();
    foreach ($executable->displayHandlers as $display) {
      if ($display->hasPath()) {
        $path = $display->getPath();
        if ($view->status() && strpos($path, '%') === FALSE) {
          $all_paths[] = l('/' . $path, $path);
        }
        else {
          $all_paths[] = String::checkPlain('/' . $path);
        }
      }
    }
    return array_unique($all_paths);
  }

}
