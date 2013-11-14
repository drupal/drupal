<?php

/**
 * @file
 * Contains \Drupal\filter\Form\FilterDisableForm.
 */

namespace Drupal\filter\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Provides the filter format disable form.
 */
class FilterDisableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to disable the text format %format?', array('%format' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'filter.admin_overview',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Disabled text formats are completely removed from the administrative interface, and any content stored with that format will not be displayed. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->disable()->save();
    drupal_set_message(t('Disabled text format %format.', array('%format' => $this->entity->label())));

    $form_state['redirect_route']['route_name'] = 'filter.admin_overview';
  }

}
