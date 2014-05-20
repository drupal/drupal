<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Form\MenuLinkResetForm.
 */

namespace Drupal\menu_ui\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for resetting a single modified menu link.
 */
class MenuLinkResetForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to reset the link %item to its default values?', array('%item' => $this->entity->link_title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('menu_ui.menu_edit', array(
      'menu' => $this->entity->menu_name,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Any customizations will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity = $this->entity->reset();
    drupal_set_message(t('The menu link was reset to its default settings.'));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
