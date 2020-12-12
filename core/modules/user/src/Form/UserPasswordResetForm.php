<?php

namespace Drupal\user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * Form controller for the user password forms.
 *
 * Users followed the link in the email, now they can enter a new password.
 *
 * @internal
 */
class UserPasswordResetForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_pass_reset';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User requesting reset.
   * @param string $expiration_date
   *   Formatted expiration date for the login link.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL, $expiration_date = NULL, $timestamp = NULL, $hash = NULL) {
    if ($expiration_date) {
      $form['message'] = ['#markup' => $this->t('<p>This is a one-time login for %user_name and will expire on %expiration_date.</p><p>Click on this button to log in to the site and change your password.</p>', ['%user_name' => $user->getAccountName(), '%expiration_date' => $expiration_date])];
      $form['#title'] = $this->t($user->getLastLoginTime() ? 'Reset password' : 'Set password');
    }
    else {
      // There is no "will be removed in" for this deprecation; the user.reset
      // route enforces an expiration date (calculated from the mandatory
      // timestamp in the URL) since Drupal 9.2.0 for security reasons.
      @trigger_error('The expiration date argument to UserPasswordResetForm::buildForm() is mandatory from drupal:9.2.0.', E_USER_DEPRECATED);
      $form['message'] = ['#markup' => $this->t('<p>This is a one-time login for %user_name.</p><p>Click on this button to log in to the site and change your password.</p>', ['%user_name' => $user->getAccountName()])];
      $form['#title'] = $this->t('Set password');
    }

    $form['help'] = ['#markup' => '<p>' . $this->t('This login can be used only once.') . '</p>'];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
    ];
    $form['#action'] = Url::fromRoute('user.reset.login', [
      'uid' => $user->id(),
      'timestamp' => $timestamp,
      'hash' => $hash,
    ])->toString();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form works by submitting the hash and timestamp to the user.reset
    // route with a 'login' action.
  }

}
