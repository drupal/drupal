<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Menu\Form\ViewsMenuLinkForm.
 */

namespace Drupal\views\Plugin\Menu\Form;

use Drupal\Core\Menu\Form\MenuLinkDefaultForm;

/**
 * Provides a form to edit Views menu links.
 *
 * This provides the feature to edit the title and description, in contrast to
 * the default menu link form.
 *
 * @see \Drupal\views\Plugin\Menu\ViewsMenuLink
 */
class ViewsMenuLinkForm extends MenuLinkDefaultForm {

  /**
   * The edited views menu link.
   *
   * @var \Drupal\views\Plugin\Menu\ViewsMenuLink
   */
  protected $menuLink;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {

    // Put the title field first.
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      // @todo Ensure that the view is not loaded with a localized title.
      //   https://www.drupal.org/node/2309507
      '#default_value' => $this->menuLink->getTitle(),
      '#weight' => -10,
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Shown when hovering over the menu link.'),
      // @todo Ensure that the view is not loaded with a localized description.
      //   https://www.drupal.org/node/2309507
      '#default_value' => $this->menuLink->getDescription(),
      '#weight' => -5,
    );

    $form += parent::buildConfigurationForm($form, $form_state);

    $form['info']['#weight'] = -8;
    $form['path']['#weight'] = -7;

    $view = $this->menuLink->loadView();
    $id = $view->storage->id();
    $label = $view->storage->label();
    if ($this->moduleHandler->moduleExists('views_ui')) {
      $message = $this->t('This link is provided by the Views module. The path can be changed by editing the view <a href="@url">@label</a>', array('@url' => \Drupal::url('views_ui.edit', array('view' => $id)), '@label' => $label));
    }
    else {
      $message = $this->t('This link is provided by the Views module from view %label.', array('%label' => $label));
    }
    $form['info']['#title'] = $message;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array &$form, array &$form_state) {
    $definition = parent::extractFormValues($form, $form_state);
    $definition['title'] = $form_state['values']['title'];
    $definition['description'] = $form_state['values']['description'];

    return $definition;
  }

}
