<?php

/**
 * @file
 * Contains \Drupal\block\Form\CustomBlockDeleteForm.
 */

namespace Drupal\custom_block\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a confirmation form for deleting a custom block entity.
 */
class CustomBlockDeleteForm extends EntityNGConfirmFormBase {

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
    return array(
      'route_name' => 'block.admin_display',
    );
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
  public function buildForm(array $form, array &$form_state, Request $request = NULL) {
    $form = parent::buildForm($form, $form_state, $request);
    $instances = $this->entity->getInstances();

    $form['message'] = array(
      '#type' => 'markup',
      '#markup' => format_plural(count($instances), 'This will also remove 1 placed block instance.', 'This will also remove @count placed block instances.'),
      '#access' => !empty($instances),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Custom block %label has been deleted.', array('%label' => $this->entity->label())));
    watchdog('custom_block', 'Custom block %label has been deleted.', array('%label' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/structure/block/custom-blocks';
  }

}
