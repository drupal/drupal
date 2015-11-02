<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Menu\ViewsMenuLink.
 */

namespace Drupal\views\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines menu links provided by views.
 *
 * @see \Drupal\views\Plugin\Derivative\ViewsMenuLink
 */
class ViewsMenuLink extends MenuLinkBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = array(
    'menu_name' => 1,
    'parent' => 1,
    'weight' => 1,
    'expanded' => 1,
    'enabled' => 1,
    'title' => 1,
    'description' => 1,
    'metadata' => 1,
  );

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewExecutableFactory;

  /**
   * The view executable of the menu link.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * Constructs a new ViewsMenuLink.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager
   * @param \Drupal\views\ViewExecutableFactory $view_executable_factory
   *   The view executable factory
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ViewExecutableFactory $view_executable_factory) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;

    $this->entityManager = $entity_manager;
    $this->viewExecutableFactory = $view_executable_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('views.executable')
    );
  }

  /**
   * Initializes the proper view.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view executable.
   */
  public function loadView() {
    if (empty($this->view)) {
      $metadata = $this->getMetaData();
      $view_id = $metadata['view_id'];
      $display_id = $metadata['display_id'];
      $view_entity = $this->entityManager->getStorage('view')->load($view_id);
      $view = $this->viewExecutableFactory->get($view_entity);
      $view->setDisplay($display_id);
      $view->initDisplay();
      $this->view = $view;
    }
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // @todo Get the translated value from the config without instantiating the
    //   view. https://www.drupal.org/node/2310379
    return $this->loadView()->display_handler->getOption('menu')['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->loadView()->display_handler->getOption('menu')['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    return (bool) $this->loadView()->display_handler->getOption('menu')['expanded'];
  }


  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    $overrides = array_intersect_key($new_definition_values, $this->overrideAllowed);
    // Update the definition.
    $this->pluginDefinition = $overrides + $this->pluginDefinition;
    if ($persist) {
      $view = $this->loadView();
      $display = &$view->storage->getDisplay($view->current_display);
      // Just save the title to the original view.
      $changed = FALSE;
      foreach ($new_definition_values as $key => $new_definition_value) {
        if (isset($display['display_options']['menu'][$key]) && $display['display_options']['menu'][$key] != $new_definition_values[$key]) {
          $display['display_options']['menu'][$key] = $new_definition_values[$key];
          $changed = TRUE;
        }
      }
      if ($changed) {
        // @todo Improve this to not trigger a full rebuild of everything, if we
        //   just changed some properties. https://www.drupal.org/node/2310389
        $view->storage->save();
      }
    }
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
  }

}
