<?php

/**
 * @file
 * Contains \Drupal\node\Form\RebuildPermissionsForm.
 */

namespace Drupal\node\Form;

use Drupal\Core\Form\ConfirmFormBase;

class RebuildPermissionsForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_configure_rebuild_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to rebuild the permissions on site content?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'system.status',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Rebuild permissions');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action rebuilds all permissions on site content, and may be a lengthy process. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    node_access_rebuild(TRUE);
    $form_state['redirect_route']['route_name'] = 'system.status';
  }

}
