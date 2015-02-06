<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\SetCustomize.
 */

namespace Drupal\shortcut\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the shortcut set customize form.
 */
class SetCustomize extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\shortcut\ShortcutSetInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['shortcuts'] = array(
      '#tree' => TRUE,
      '#weight' => -20,
    );

    $form['shortcuts']['links'] = array(
      '#type' => 'table',
      '#header' => array(t('Name'), t('Weight'), t('Operations')),
      '#empty' => $this->t('No shortcuts available. <a href="@link">Add a shortcut</a>', array('@link' => $this->url('shortcut.link_add', array('shortcut_set' => $this->entity->id())))),
      '#attributes' => array('id' => 'shortcuts'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'shortcut-weight',
        ),
      ),
    );

    foreach ($this->entity->getShortcuts() as $shortcut) {
      $id = $shortcut->id();
      $url = $shortcut->getUrl();
      if (!$url->access()) {
        continue;
      }
      $form['shortcuts']['links'][$id]['#attributes']['class'][] = 'draggable';
      $form['shortcuts']['links'][$id]['name'] = array(
        '#type' => 'link',
        '#title' => $shortcut->getTitle(),
      ) + $url->toRenderArray();
      unset($form['shortcuts']['links'][$id]['name']['#access_callback']);
      $form['shortcuts']['links'][$id]['#weight'] = $shortcut->getWeight();
      $form['shortcuts']['links'][$id]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $shortcut->getTitle())),
        '#title_display' => 'invisible',
        '#default_value' => $shortcut->getWeight(),
        '#attributes' => array('class' => array('shortcut-weight')),
      );

      $links['edit'] = array(
        'title' => t('Edit'),
        'url' => $shortcut->urlInfo(),
      );
      $links['delete'] = array(
        'title' => t('Delete'),
        'url' => $shortcut->urlInfo('delete-form'),
      );
      $form['shortcuts']['links'][$id]['operations'] = array(
        '#type' => 'operations',
        '#links' => $links,
        '#access' => $url->access(),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#type' => 'submit',
        '#value' => t('Save changes'),
        '#access' => (bool) Element::getVisibleChildren($form['shortcuts']['links']),
        '#submit' => array('::submitForm', '::save'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    foreach ($this->entity->getShortcuts() as $shortcut) {
      $weight = $form_state->getValue(array('shortcuts', 'links', $shortcut->id(), 'weight'));
      $shortcut->setWeight($weight);
      $shortcut->save();
    }
    drupal_set_message(t('The shortcut set has been updated.'));
  }

}
