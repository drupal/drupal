<?php

/**
 * @file
 * Contains \Drupal\forum\Form\DeleteForm.
 */

namespace Drupal\forum\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\taxonomy\Plugin\Core\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the form to delete a forum term.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The taxonomy term being deleted.
   *
   * @var \Drupal\taxonomy\Plugin\Core\Entity\Term
   */
  protected $taxonomyTerm;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'forum_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the forum %label?', array('%label' => $this->taxonomyTerm->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/structure/forum';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Term $taxonomy_term = NULL, Request $request = NULL) {
    $this->taxonomyTerm = $taxonomy_term;

    return parent::buildForm($form, $form_state, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->taxonomyTerm->delete();
    drupal_set_message(t('The forum %label and all sub-forums have been deleted.', array('%label' => $this->taxonomyTerm->label())));
    watchdog('forum', 'forum: deleted %label and all its sub-forums.', array('%label' => $this->taxonomyTerm->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/structure/forum';
  }

}
