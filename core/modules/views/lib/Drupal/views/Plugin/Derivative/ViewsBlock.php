<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsBlock.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for all Views block displays.
 *
 * @see \Drupal\views\Plugin\block\block\ViewsBlock
 */
class ViewsBlock implements ContainerDerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The view storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $viewStorageController;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity.manager')->getStorageController('view')
    );
  }

  /**
   * Constructs a ViewsBlock object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $view_storage_controller
   *   The entity storage controller to load views.
   */
  public function __construct($base_plugin_id, EntityStorageControllerInterface $view_storage_controller) {
    $this->basePluginId = $base_plugin_id;
    $this->viewStorageController = $view_storage_controller;
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Check all Views for block displays.
    foreach ($this->viewStorageController->loadMultiple() as $view) {
      // Do not return results for disabled views.
      if (!$view->status()) {
        continue;
      }
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display) {
        // Add a block plugin definition for each block display.
        if (isset($display) && !empty($display->definition['uses_hook_block'])) {
          $delta = $view->id() . '-' . $display->display['id'];
          $desc = $display->getOption('block_description');

          if (empty($desc)) {
            if ($display->display['display_title'] == $display->definition['title']) {
              $desc = t('!view', array('!view' => $view->label()));
            }
            else {
              $desc = t('!view: !display', array('!view' => $view->label(), '!display' => $display->display['display_title']));
            }
          }
          $this->derivatives[$delta] = array(
            'category' => $display->getOption('block_category'),
            'admin_label' => $desc,
            'cache' => $display->getCacheType()
          );
          $this->derivatives[$delta] += $base_plugin_definition;
        }
      }
    }
    return $this->derivatives;
  }

}
