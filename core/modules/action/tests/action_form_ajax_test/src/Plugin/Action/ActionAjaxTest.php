<?php

namespace Drupal\action_form_ajax_test\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin used for testing AJAX in action config entity forms.
 *
 * @Action(
 *   id = "action_form_ajax_test",
 *   label = @Translation("action_form_ajax_test"),
 *   type = "system"
 * )
 */
class ActionAjaxTest extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'party_time' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $having_a_party = $form_state->getValue('having_a_party', !empty($this->configuration['party_time']));
    $form['having_a_party'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Are we having a party?'),
      '#ajax' => [
        'wrapper' => 'party-container',
        'callback' => [$this, 'partyCallback'],
      ],
      '#default_value' => $having_a_party,
    ];
    $form['container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="party-container">',
      '#suffix' => '</div>',
    ];

    if ($having_a_party) {
      $form['container']['party_time'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Party time'),
        '#default_value' => $this->configuration['party_time'],
      ];
    }

    return $form;
  }

  /**
   * Callback for party checkbox.
   */
  public function partyCallback(array $form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['party_time'] = $form_state->getValue('party_time');
  }

}
