<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\Form\MenuLinkFormInterface.
 */

namespace Drupal\Core\Menu\Form;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Menu\MenuLinkInterface;

/**
 * Defines an interface for edit forms for menu links.
 *
 * All menu link plugins use the same interface for their configuration or
 * editing form, but the implementations may differ.
 * @see \Drupal\Core\Menu\MenuLinkInterface::getFormClass()
 */
interface MenuLinkFormInterface extends PluginFormInterface {

  /**
   * Injects the menu link plugin.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link
   *   A menu link plugin instance.
   */
  public function setMenuLinkInstance(MenuLinkInterface $menu_link);

  /**
   * Form plugin helper.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   The new plugin definition values takes from the form values.
   */
  public function extractFormValues(array &$form, array &$form_state);

}
