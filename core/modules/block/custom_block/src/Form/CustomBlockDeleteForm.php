<?php

/**
 * @file
 * Contains \Drupal\block\Form\CustomBlockDeleteForm.
 */

namespace Drupal\custom_block\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for deleting a custom block entity.
 */
class CustomBlockDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('block.admin_display');
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
  public function buildForm(array $form, array &$form_state) {
    $instances = $this->entity->getInstances();

    $form['message'] = array(
      '#markup' => format_plural(count($instances), 'This will also remove 1 placed block instance.', 'This will also remove @count placed block instances.'),
      '#access' => !empty($instances),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Custom block %label has been deleted.', array('%label' => $this->entity->label())));
    watchdog('custom_block', 'Custom block %label has been deleted.', array('%label' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect_route'] = new Url('custom_block.list');
  }

}
