<?php

namespace Drupal\shortcut\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Builds the shortcut set customize form.
 *
 * @internal
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
    $form['shortcuts'] = [
      '#tree' => TRUE,
      '#weight' => -20,
    ];

    $form['shortcuts']['links'] = [
      '#type' => 'table',
      '#header' => [t('Name'), t('Weight'), t('Operations')],
      '#empty' => $this->t('No shortcuts available. <a href=":link">Add a shortcut</a>', [':link' => Url::fromRoute('shortcut.link_add', ['shortcut_set' => $this->entity->id()])->toString()]),
      '#attributes' => ['id' => 'shortcuts'],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'shortcut-weight',
        ],
      ],
    ];

    foreach ($this->entity->getShortcuts() as $shortcut) {
      $id = $shortcut->id();
      $url = $shortcut->getUrl();
      if (!$url->access()) {
        continue;
      }
      $form['shortcuts']['links'][$id]['#attributes']['class'][] = 'draggable';
      $form['shortcuts']['links'][$id]['name'] = [
        '#type' => 'link',
        '#title' => $shortcut->getTitle(),
      ] + $url->toRenderArray();
      unset($form['shortcuts']['links'][$id]['name']['#access_callback']);
      $form['shortcuts']['links'][$id]['#weight'] = $shortcut->getWeight();
      $form['shortcuts']['links'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $shortcut->getTitle()]),
        '#title_display' => 'invisible',
        '#default_value' => $shortcut->getWeight(),
        '#attributes' => ['class' => ['shortcut-weight']],
      ];

      $links['edit'] = [
        'title' => t('Edit'),
        'url' => $shortcut->toUrl(),
      ];
      $links['delete'] = [
        'title' => t('Delete'),
        'url' => $shortcut->toUrl('delete-form'),
      ];
      $form['shortcuts']['links'][$id]['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
        '#access' => $url->access(),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#access' => (bool) Element::getVisibleChildren($form['shortcuts']['links']),
        '#submit' => ['::submitForm', '::save'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    foreach ($this->entity->getShortcuts() as $shortcut) {
      $weight = $form_state->getValue(['shortcuts', 'links', $shortcut->id(), 'weight']);
      $shortcut->setWeight($weight);
      $shortcut->save();
    }
    $this->messenger()->addStatus($this->t('The shortcut set has been updated.'));
  }

}
