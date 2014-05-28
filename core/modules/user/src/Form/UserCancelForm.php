<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserCancelForm.
 */

namespace Drupal\user\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides a confirmation form for cancelling user account.
 */
class UserCancelForm extends ContentEntityConfirmFormBase {

  /**
   * Available account cancellation methods.
   *
   * @var array
   */
  protected $cancelMethods;

  /**
   * The user being cancelled.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->entity->id() == $this->currentUser()->id()) {
      return $this->t('Are you sure you want to cancel your account?');
    }
    return $this->t('Are you sure you want to cancel the account %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $description = '';
    $default_method = $this->config('user.settings')->get('cancel_method');
    if ($this->currentUser()->hasPermission('administer users') || $this->currentUser()->hasPermission('select account cancellation method')) {
      $description = $this->t('Select the method to cancel the account above.');
    }
    // Options supplied via user_cancel_methods() can have a custom
    // #confirm_description property for the confirmation form description.
    elseif (isset($this->cancelMethods[$default_method]['#confirm_description'])) {
      $description = $this->cancelMethods[$default_method]['#confirm_description'];
    }
    return $description . ' ' . $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel account');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $user = $this->currentUser();
    $this->cancelMethods = user_cancel_methods();

    // Display account cancellation method selection, if allowed.
    $admin_access = $user->hasPermission('administer users');
    $form['user_cancel_method'] = array(
      '#type' => 'radios',
      '#title' => ($this->entity->id() == $user->id() ? $this->t('When cancelling your account') : $this->t('When cancelling the account')),
      '#access' => $admin_access || $user->hasPermission('select account cancellation method'),
    );
    $form['user_cancel_method'] += $this->cancelMethods;

    // Allow user administrators to skip the account cancellation confirmation
    // mail (by default), as long as they do not attempt to cancel their own
    // account.
    $override_access = $admin_access && ($this->entity->id() != $user->id());
    $form['user_cancel_confirm'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Require e-mail confirmation to cancel account.'),
      '#default_value' => !$override_access,
      '#access' => $override_access,
      '#description' => $this->t('When enabled, the user must confirm the account cancellation via e-mail.'),
    );
    // Also allow to send account canceled notification mail, if enabled.
    $default_notify = $this->config('user.settings')->get('notify.status_canceled');
    $form['user_cancel_notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is canceled.'),
      '#default_value' => ($override_access ? FALSE : $default_notify),
      '#access' => $override_access && $default_notify,
      '#description' => $this->t('When enabled, the user will receive an e-mail notification after the account has been canceled.'),
    );

    // Always provide entity id in the same form key as in the entity edit form.
    $form['uid'] = array('#type' => 'value', '#value' => $this->entity->id());

    $form = parent::buildForm($form, $form_state);

    // @todo Convert to getCancelRoute() after https://drupal.org/node/1987896.
    $form['actions']['cancel'] += $this->entity->urlInfo()->toRenderArray();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    // Cancel account immediately, if the current user has administrative
    // privileges, no confirmation mail shall be sent, and the user does not
    // attempt to cancel the own account.
    if ($this->currentUser()->hasPermission('administer users') && empty($form_state['values']['user_cancel_confirm']) && $this->entity->id() != $this->currentUser()->id()) {
      user_cancel($form_state['values'], $this->entity->id(), $form_state['values']['user_cancel_method']);

      $form_state['redirect_route']['route_name'] = 'user.admin_account';
    }
    else {
      // Store cancelling method and whether to notify the user in
      // $this->entity for user_cancel_confirm().
      $this->entity->user_cancel_method = $form_state['values']['user_cancel_method'];
      $this->entity->user_cancel_notify = $form_state['values']['user_cancel_notify'];
      $this->entity->save();
      _user_mail_notify('cancel_confirm', $this->entity);
      drupal_set_message($this->t('A confirmation request to cancel your account has been sent to your e-mail address.'));
      watchdog('user', 'Sent account cancellation request to %name %email.', array('%name' => $this->entity->label(), '%email' => '<' . $this->entity->getEmail() . '>'), WATCHDOG_NOTICE);

      $form_state['redirect_route'] = array(
        'route_name' => 'user.view',
        'route_parameters' => array('user' => $this->entity->id()),
      );
    }
  }

}
