<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Form\MenuLinkResetForm.
 */

namespace Drupal\menu_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form for resetting a single modified menu link.
 */
class MenuLinkResetForm extends ConfirmFormBase {

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The menu link.
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface
   */
  protected $link;

  /**
   * Constructs a MenuLinkResetForm object.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager) {
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_link_reset_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the link %item to its default values?', array('%item' => $this->link->getTitle()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('menu_ui.menu_edit', array(
      'menu' => $this->link->getMenuName(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Any customizations will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, MenuLinkInterface $menu_link_plugin = NULL) {
    $this->link = $menu_link_plugin;

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->link = $this->menuLinkManager->resetLink($this->link->getPluginId());
    drupal_set_message($this->t('The menu link was reset to its default settings.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Checks access based on whether the link can be reset.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin
   *   The menu link plugin being checked.
   *
   * @return string
   *   AccessInterface::ALLOW when access was granted, otherwise
   *   AccessInterface::DENY.
   */
  public function linkIsResettable(MenuLinkInterface $menu_link_plugin) {
    return $menu_link_plugin->isResettable() ? AccessInterface::ALLOW : AccessInterface::DENY;
  }

}
