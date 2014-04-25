<?php

/**
 * @file
 * Contains \Drupal\path\Form\EditForm.
 */

namespace Drupal\path\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Url;

/**
 * Provides the path edit form.
 */
class EditForm extends PathFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'path_admin_edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPath($pid) {
    return $this->aliasStorage->load(array('pid' => $pid));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $pid = NULL) {
    $form = parent::buildForm($form, $form_state, $pid);

    $form['#title'] = String::checkPlain($this->path['alias']);
    $form['pid'] = array(
      '#type' => 'hidden',
      '#value' => $this->path['pid'],
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#submit' => array(array($this, 'deleteSubmit')),
    );
    return $form;
  }

  /**
   * Submits the delete form.
   */
  public function deleteSubmit(array &$form, array &$form_state) {
    $form_state['redirect_route'] = new Url('path.delete', array(
      'pid' => $form_state['values']['pid'],
    ));

    if ($this->getRequest()->query->has('destination')) {
      $form_state['redirect_route']->setOption('query', drupal_get_destination());
      $this->getRequest()->query->remove('destination');
    }
  }

}
