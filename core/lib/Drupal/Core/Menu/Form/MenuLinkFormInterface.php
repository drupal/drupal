<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\Form\MenuLinkFormInterface.
 */

namespace Drupal\Core\Menu\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for edit forms of menu links.
 *
 * All menu link plugins use the same interface for their configuration or
 * editing form, but the implementations may differ.
 *
 * @see \Drupal\Core\Menu\MenuLinkInterface::getFormClass()
 */
interface MenuLinkFormInterface extends PluginFormInterface {

  /**
   * Injects the menu link plugin instance.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link
   *   A menu link plugin instance.
   */
  public function setMenuLinkInstance(MenuLinkInterface $menu_link);

  /**
   * Extracts a plugin definition from form values.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The new plugin definition values taken from the form values.
   */
  public function extractFormValues(array &$form, FormStateInterface $form_state);

}
