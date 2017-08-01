<?php

namespace Drupal\content_moderation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\Plugin\WorkflowTypeStateFormBase;
use Drupal\workflows\StateInterface;

/**
 * The content moderation state form.
 *
 * @see \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration
 */
class ContentModerationStateForm extends WorkflowTypeStateFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, StateInterface $state = NULL) {
    /** @var \Drupal\content_moderation\ContentModerationState $state */
    $state = $form_state->get('state');
    $is_required_state = isset($state) ? in_array($state->id(), $this->workflowType->getRequiredStates(), TRUE) : FALSE;

    $form = [];
    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#description' => $this->t('When content reaches this state it should be published.'),
      '#default_value' => isset($state) ? $state->isPublishedState() : FALSE,
      '#disabled' => $is_required_state,
    ];

    $form['default_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default revision'),
      '#description' => $this->t('When content reaches this state it should be made the default revision; this is implied for published states.'),
      '#default_value' => isset($state) ? $state->isDefaultRevisionState() : FALSE,
      '#disabled' => $is_required_state,
      // @todo Add form #state to force "make default" on when "published" is
      // on for a state.
      // @see https://www.drupal.org/node/2645614
    ];
    return $form;
  }

}
