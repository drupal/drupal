<?php

namespace Drupal\media;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the media edit forms.
 *
 * @internal
 */
class MediaForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = $this->entity->bundle->entity;

    if ($this->operation === 'edit') {
      $form['#title'] = $this->t('Edit %type_label @label', [
        '%type_label' => $media_type->label(),
        '@label' => $this->entity->label(),
      ]);
    }

    // Media author information for administrators.
    if (isset($form['uid']) || isset($form['created'])) {
      $form['author'] = [
        '#type' => 'details',
        '#title' => $this->t('Authoring information'),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['media-form-author'],
        ],
        '#weight' => 90,
        '#optional' => TRUE,
      ];
    }

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    $form['#attached']['library'][] = 'media/form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    $context = ['@type' => $this->entity->bundle(), '%label' => $this->entity->label(), 'link' => $this->entity->toLink($this->t('View'))->toString()];
    $logger = $this->logger('media');
    $t_args = ['@type' => $this->entity->bundle->entity->label(), '%label' => $this->entity->toLink($this->entity->label())->toString()];

    if ($saved === SAVED_NEW) {
      $logger->notice('@type: added %label.', $context);
      $this->messenger()->addStatus($this->t('@type %label has been created.', $t_args));
    }
    else {
      $logger->notice('@type: updated %label.', $context);
      $this->messenger()->addStatus($this->t('@type %label has been updated.', $t_args));
    }

    // Redirect the user to the media overview if the user has the 'access media
    // overview' permission. If not, redirect to the canonical URL of the media
    // item.
    if ($this->currentUser()->hasPermission('access media overview')) {
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
    else {
      $form_state->setRedirectUrl($this->entity->toUrl());
    }

    return $saved;
  }

}
