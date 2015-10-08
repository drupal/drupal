<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\views\field\UserBulkForm.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\user\UserInterface;

/**
 * Defines a user operations bulk form element.
 *
 * @ViewsField("user_bulk_form")
 */
class UserBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   *
   * Provide a more useful title to improve the accessibility.
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    parent::viewsForm($form, $form_state);

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row_index => $result) {
        $account = $result->_entity;
        if ($account instanceof UserInterface) {
          $form[$this->options['id']][$row_index]['#title'] = $this->t('Update the user %name', array('%name' => $account->label()));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No users selected.');
  }

}
