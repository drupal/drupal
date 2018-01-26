<?php

namespace Drupal\comment\Form;

use Drupal\Core\Entity\Form\DeleteMultipleForm as EntityDeleteMultipleForm;
use Drupal\Core\Url;

/**
 * Provides the comment multiple delete confirmation form.
 *
 * @internal
 */
class ConfirmDeleteMultiple extends EntityDeleteMultipleForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->selection), 'Are you sure you want to delete this comment and all its children?', 'Are you sure you want to delete these comments and all their children?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('comment.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletedMessage($count) {
    return $this->formatPlural($count, 'Deleted @count comment.', 'Deleted @count comments.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural($count, "@count comment has not been deleted because you do not have the necessary permissions.", "@count comments have not been deleted because you do not have the necessary permissions.");
  }

}
