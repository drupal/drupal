<?php

/**
 * @file
 * Contains \Drupal\forum\Form\DeleteForm.
 */

namespace Drupal\forum\Form;

use Drupal\Core\Form\ConfirmFormBase;
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
  public function getFormID() {
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
  public function getCancelRoute() {
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
  public function buildForm(array $form, array &$form_state, TermInterface $taxonomy_term = NULL) {
    $this->taxonomyTerm = $taxonomy_term;

    $form = parent::buildForm($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/1974210.
    $form['actions']['cancel']['#href'] = 'admin/structure/forum';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->taxonomyTerm->delete();
    drupal_set_message($this->t('The forum %label and all sub-forums have been deleted.', array('%label' => $this->taxonomyTerm->label())));
    watchdog('forum', 'forum: deleted %label and all its sub-forums.', array('%label' => $this->taxonomyTerm->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/structure/forum';
  }

}
