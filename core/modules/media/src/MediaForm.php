<?php

namespace Drupal\media;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the media edit forms.
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

    $form['#attached']['library'][] = 'media/media_form';

    $form['#entity_builders']['update_status'] = [$this, 'updateStatus'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $media = $this->entity;

    // Add a "Publish" button.
    $element['publish'] = $element['submit'];
    // If the "Publish" button is clicked, we want to update the status to
    // "published".
    $element['publish']['#published_status'] = TRUE;
    $element['publish']['#dropbutton'] = 'save';
    if ($media->isNew()) {
      $element['publish']['#value'] = $this->t('Save and publish');
    }
    else {
      $element['publish']['#value'] = $media->isPublished() ? $this->t('Save and keep published') : $this->t('Save and publish');
    }
    $element['publish']['#weight'] = 0;

    // Add a "Unpublish" button.
    $element['unpublish'] = $element['submit'];
    // If the "Unpublish" button is clicked, we want to update the status to
    // "unpublished".
    $element['unpublish']['#published_status'] = FALSE;
    $element['unpublish']['#dropbutton'] = 'save';
    if ($media->isNew()) {
      $element['unpublish']['#value'] = $this->t('Save as unpublished');
    }
    else {
      $element['unpublish']['#value'] = !$media->isPublished() ? $this->t('Save and keep unpublished') : $this->t('Save and unpublish');
    }
    $element['unpublish']['#weight'] = 10;

    // If already published, the 'publish' button is primary.
    if ($media->isPublished()) {
      $element['publish']['#button_type'] = 'primary';
    }
    // Otherwise, the 'unpublish' button is primary and should come first.
    else {
      $element['unpublish']['#button_type'] = 'primary';
      $element['unpublish']['#weight'] = -10;
    }

    // Remove the "Save" button.
    $element['submit']['#access'] = FALSE;

    $element['delete']['#access'] = $media->access('delete');
    $element['delete']['#weight'] = 100;

    return $element;
  }

  /**
   * Entity builder updating the media status with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\media\MediaInterface $media
   *   The media updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\media\MediaForm::form()
   */
  public function updateStatus($entity_type_id, MediaInterface $media, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (!empty($element['#published_status'])) {
      $media->setPublished();
    }
    else {
      $media->setUnpublished();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    $context = ['@type' => $this->entity->bundle(), '%label' => $this->entity->label()];
    $logger = $this->logger('media');
    $t_args = ['@type' => $this->entity->bundle->entity->label(), '%label' => $this->entity->label()];

    if ($saved === SAVED_NEW) {
      $logger->notice('@type: added %label.', $context);
      drupal_set_message($this->t('@type %label has been created.', $t_args));
    }
    else {
      $logger->notice('@type: updated %label.', $context);
      drupal_set_message($this->t('@type %label has been updated.', $t_args));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('canonical'));
    return $saved;
  }

}
