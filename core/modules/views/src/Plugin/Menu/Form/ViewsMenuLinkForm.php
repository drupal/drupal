<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Menu\Form\ViewsMenuLinkForm.
 */

namespace Drupal\views\Plugin\Menu\Form;

use Drupal\Core\Menu\Form\MenuLinkDefaultForm;
use Drupal\views\Plugin\Menu\ViewsMenuLink;

class ViewsMenuLinkForm extends MenuLinkDefaultForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {

    // Put the title field first.
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->menuLink->getTitle(),
    );

    $form += parent::buildConfigurationForm($form, $form_state);

    if ($this->menuLink instanceof ViewsMenuLink) {
      $view = $this->menuLink->loadView();
      $id = $view->storage->id();
      $label = $view->storage->label();
      if ($this->moduleHandler->moduleExists('views_ui')) {
        $message = $this->t('This link is provided by the Views module. The path can be changed by editing the view !editlink.', array('!editlink' => \Drupal::l($label, 'views_ui.edit', array('view' => $id))));
      }
      else {
        $message = $this->t('This link is provided by the Views module from view %label.', array('%label' => $label));
      }
      $form['info']['#title'] = $message;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array &$form, array &$form_state) {
    $definition = parent::extractFormValues($form, $form_state);
    $definition['title'] = $form_state['values']['title'];

    return $definition;
  }

}
