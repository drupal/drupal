<?php

namespace Drupal\menu_ui\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form for deletion of a custom menu.
 */
class MenuDeleteForm extends EntityDeleteForm {

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new MenuDeleteForm.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, Connection $connection) {
    $this->menuLinkManager = $menu_link_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $caption = '';
    $num_links = $this->menuLinkManager->countMenuLinks($this->entity->id());
    if ($num_links) {
      $caption .= '<p>' . $this->formatPlural($num_links, '<strong>Warning:</strong> There is currently 1 menu link in %title. It will be deleted (system-defined items will be reset).', '<strong>Warning:</strong> There are currently @count menu links in %title. They will be deleted (system-defined links will be reset).', array('%title' => $this->entity->label())) . '</p>';
    }
    $caption .= '<p>' . t('This action cannot be undone.') . '</p>';
    return $caption;
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    $this->logger('menu')->notice('Deleted custom menu %title and all its menu links.', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Locked menus may not be deleted.
    if ($this->entity->isLocked()) {
      return;
    }

    // Delete all links to the overview page for this menu.
    // @todo Add a more generic helper function to the menu link plugin
    //   manager to remove links to a entity or other ID used as a route
    //   parameter that is being removed. Also, consider moving this to
    //   menu_ui.module as part of a generic response to entity deletion.
    //   https://www.drupal.org/node/2310329
    $menu_links = $this->menuLinkManager->loadLinksByRoute('entity.menu.edit_form', array('menu' => $this->entity->id()), TRUE);
    foreach ($menu_links as $id => $link) {
      $this->menuLinkManager->removeDefinition($id);
    }

    parent::submitForm($form, $form_state);
  }
}
