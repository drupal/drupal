<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Block\ViewsBlockBase.
 */

namespace Drupal\views\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\ViewExecutableFactory;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for Views block plugins.
 */
abstract class ViewsBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The View executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The display ID being used for this View.
   *
   * @var string
   */
  protected $displayID;

  /**
   * Indicates whether the display was successfully set.
   *
   * @var bool
   */
  protected $displaySet;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The views storage controller.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ViewExecutableFactory $executable_factory, EntityStorageControllerInterface $storage_controller) {
    $this->pluginId = $plugin_id;
    list($plugin, $delta) = explode(':', $this->getPluginId());
    list($name, $this->displayID) = explode('-', $delta, 2);
    // Load the view.
    $view = $storage_controller->load($name);
    $this->view = $executable_factory->get($view);
    $this->displaySet = $this->view->setDisplay($this->displayID);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('views.executable'),
      $container->get('entity.manager')->getStorageController('view')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $this->view->access($this->displayID);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Set the default label to '' so the views internal title is used.
    $form['label']['#default_value'] = '';
    $form['label']['#access'] = FALSE;

    return $form;
  }

  /**
   * Converts Views block content to a renderable array with contextual links.
   *
   * @param string|array $output
   *   An string|array representing the block. This will be modified to be a
   *   renderable array, containing the optional '#contextual_links' property (if
   *   there are any contextual links associated with the block).
   * @param string $block_type
   *   The type of the block. If it's 'block' it's a regular views display,
   *   but 'exposed_filter' exist as well.
   */
  protected function addContextualLinks(&$output, $block_type = 'block') {
    // Do not add contextual links to an empty block.
    if (!empty($output)) {
      // Contextual links only work on blocks whose content is a renderable
      // array, so if the block contains a string of already-rendered markup,
      // convert it to an array.
      if (is_string($output)) {
        $output = array('#markup' => $output);
      }
      // Add the contextual links.
      views_add_contextual_links($output, $block_type, $this->view, $this->displayID);
    }
  }

}
