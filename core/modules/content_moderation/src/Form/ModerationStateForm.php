<?php

namespace Drupal\content_moderation\Form;

use Drupal\content_moderation\Entity\ModerationState;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ModerationStateForm.
 */
class ModerationStateForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\content_moderation\ModerationStateInterface $moderation_state */
    $moderation_state = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $moderation_state->label(),
      '#description' => $this->t('Label for the Moderation state.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $moderation_state->id(),
      '#machine_name' => array(
        'exists' => [ModerationState::class, 'load'],
      ),
      '#disabled' => !$moderation_state->isNew(),
    );

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#description' => $this->t('When content reaches this state it should be published.'),
      '#default_value' => $moderation_state->isPublishedState(),
    ];

    $form['default_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default revision'),
      '#description' => $this->t('When content reaches this state it should be made the default revision; this is implied for published states.'),
      '#default_value' => $moderation_state->isDefaultRevisionState(),
      // @todo Add form #state to force "make default" on when "published" is
      // on for a state.
      // @see https://www.drupal.org/node/2645614
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $moderation_state = $this->entity;
    $status = $moderation_state->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Moderation state.', [
          '%label' => $moderation_state->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Moderation state.', [
          '%label' => $moderation_state->label(),
        ]));
    }
    $form_state->setRedirectUrl($moderation_state->toUrl('collection'));
  }

}
