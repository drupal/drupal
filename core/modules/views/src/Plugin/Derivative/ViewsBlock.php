<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsBlock.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for all Views block displays.
 *
 * @see \Drupal\views\Plugin\block\block\ViewsBlock
 */
class ViewsBlock implements ContainerDeriverInterface {

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
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity.manager')->getStorage('view')
    );
  }

  /**
   * Constructs a ViewsBlock object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The entity storage to load views.
   */
  public function __construct($base_plugin_id, EntityStorageInterface $view_storage) {
    $this->basePluginId = $base_plugin_id;
    $this->viewStorage = $view_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Check all Views for block displays.
    foreach ($this->viewStorage->loadMultiple() as $view) {
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

          $admin_label = $display->getOption('block_description');
          if (empty($admin_label)) {
            if ($display->display['display_title'] == $display->definition['title']) {
              $admin_label = $view->label();
            }
            else {
              // Allow translators to control the punctuation. Plugin
              // definitions get cached, so use TranslationWrapper() instead of
              // t() to avoid double escaping when $admin_label is rendered
              // during requests that use the cached definition.
              $admin_label = new TranslationWrapper('@view: @display', ['@view' => $view->label(), '@display' => $display->display['display_title']]);
            }
          }

          $this->derivatives[$delta] = array(
            'category' => $display->getOption('block_category'),
            'admin_label' => $admin_label,
            'config_dependencies' => array(
              'config' => array(
                $view->getConfigDependencyName(),
              )
            )
          );
          $this->derivatives[$delta] += $base_plugin_definition;
        }
      }
    }
    return $this->derivatives;
  }

}
