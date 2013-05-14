<?php

/**
 * @file
 * Contains \Drupal\block\Form\AdminBlockDeleteForm.
 */

namespace Drupal\block\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\block\Plugin\Core\Entity\Block;

/**
 * Provides a deletion confirmation form for the block instance deletion form.
 */
class AdminBlockDeleteForm extends ConfirmFormBase {

  /**
   * The block being deleted.
   *
   * @var \Drupal\block\Plugin\Core\Entity\Block
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'block_admin_block_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the block %name?', array('%name' => $this->block->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/structure/block';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param \Drupal\block\Plugin\Core\Entity\Block $block
   *   The block instance.
   */
  public function buildForm(array $form, array &$form_state, Block $block = null) {
    $this->block = $block;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->block->delete();
    drupal_set_message(t('The block %name has been removed.', array('%name' => $this->block->label())));
    $form_state['redirect'] = 'admin/structure/block';
  }

}
