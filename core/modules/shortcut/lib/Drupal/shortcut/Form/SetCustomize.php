<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\SetCustomize.
 */

namespace Drupal\shortcut\Form;

use Drupal\Core\Entity\EntityFormController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the shortcut set customize form.
 */
class SetCustomize extends EntityFormController {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $form['shortcuts'] = array(
      '#tree' => TRUE,
      '#weight' => -20,
    );

    $form['shortcuts']['links'] = array(
      '#type' => 'table',
      '#header' => array(t('Name'), t('Weight'), t('Operations')),
      '#empty' => t('No shortcuts available. @link', array('@link' => l(t('Add a shortcut'), 'admin/config/user-interface/shortcut/' . $this->entity->id() . '/add-link'))),
      '#attributes' => array('id' => 'shortcuts'),
      '#tabledrag' => array(
        array('order', 'sibling', 'shortcut-weight'),
      ),
    );

    foreach ($this->entity->links as $link) {
      $mlid = $link->id();
      $form['shortcuts']['links'][$mlid]['#attributes']['class'][] = 'draggable';
      $form['shortcuts']['links'][$mlid]['name']['#markup'] = l($link->link_title, $link->link_path);
      $form['shortcuts']['links'][$mlid]['#weight'] = $link->weight;
      $form['shortcuts']['links'][$mlid]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $link->link_title)),
        '#title_display' => 'invisible',
        '#default_value' => $link->weight,
        '#attributes' => array('class' => array('shortcut-weight')),
      );

      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => "admin/config/user-interface/shortcut/link/$mlid",
      );
      $links['delete'] = array(
        'title' => t('Delete'),
        'href' => "admin/config/user-interface/shortcut/link/$mlid/delete",
      );
      $form['shortcuts']['links'][$mlid]['operations'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }
    // Sort the list so the output is ordered by weight.
    uasort($form['shortcuts']['links'], 'element_sort');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#value' => t('Save changes'),
        '#access' => !empty($this->entity->links),
        '#submit' => array(
          array($this, 'submit'),
          array($this, 'save'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    foreach ($this->entity->links as $link) {
      $link->weight = $form_state['values']['shortcuts']['links'][$link->mlid]['weight'];
      $link->save();
    }
    drupal_set_message(t('The shortcut set has been updated.'));
  }

}
