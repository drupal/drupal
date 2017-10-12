<?php

namespace Drupal\menu_link_content\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a delete form for content menu links.
 *
 * @internal
 */
class MenuLinkContentDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->moduleHandler->moduleExists('menu_ui')) {
      return new Url('entity.menu.edit_form', ['menu' => $this->entity->getMenuName()]);
    }
    return $this->entity->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The menu link %title has been deleted.', ['%title' => $this->entity->label()]);
  }

}
