<?php

/**
 * @file
 * Contains \Drupal\action\Plugin\Action\GotoAction.
 */

namespace Drupal\action\Plugin\Action;

use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Action\ConfigurableActionBase;

/**
 * Redirects to a different URL.
 *
 * @Action(
 *   id = "action_goto_action",
 *   label = @Translation("Redirect to URL"),
 *   type = "system"
 * )
 */
class GotoAction extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    drupal_goto($this->configuration['url']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array(
      'url' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('The URL to which the user should be redirected. This can be an internal URL like node/1234 or an external URL like @url.', array('@url' => 'http://drupal.org')),
      '#default_value' => $this->configuration['url'],
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$form, array &$form_state) {
    $this->configuration['url'] = $form_state['values']['url'];
  }

}
