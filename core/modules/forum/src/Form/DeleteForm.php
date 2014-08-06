<?php

/**
 * @file
 * Contains \Drupal\forum\Form\DeleteForm.
 */

namespace Drupal\forum\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;

/**
 * Builds the form to delete a forum term.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The taxonomy term being deleted.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $taxonomyTerm;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forum_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the forum %label?', array('%label' => $this->taxonomyTerm->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('forum.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TermInterface $taxonomy_term = NULL) {
    $this->taxonomyTerm = $taxonomy_term;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->taxonomyTerm->delete();
    drupal_set_message($this->t('The forum %label and all sub-forums have been deleted.', array('%label' => $this->taxonomyTerm->label())));
    $this->logger('forum')->notice('forum: deleted %label and all its sub-forums.', array('%label' => $this->taxonomyTerm->label()));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
