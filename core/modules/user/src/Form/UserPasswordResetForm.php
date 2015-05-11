<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserPasswordResetForm.
 */

namespace Drupal\user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the user password forms.
 */
class UserPasswordResetForm extends FormBase {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new UserPasswordResetForm.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('user')
    );
  }

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
   *   Formatted expiration date for the login link, or NULL if the link does
   *   not expire.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL, $expiration_date = NULL, $timestamp = NULL, $hash = NULL) {
    if ($expiration_date) {
      $form['message'] = array('#markup' => $this->t('<p>This is a one-time login for %user_name and will expire on %expiration_date.</p><p>Click on this button to log in to the site and change your password.</p>', array('%user_name' => $user->getUsername(), '%expiration_date' => $expiration_date)));
      $form['#title'] = $this->t('Reset password');
    }
    else {
      // No expiration for first time login.
      $form['message'] = array('#markup' => $this->t('<p>This is a one-time login for %user_name.</p><p>Click on this button to log in to the site and change your password.</p>', array('%user_name' => $user->getUsername())));
      $form['#title'] = $this->t('Set password');
    }

    $form['user'] = array(
      '#type' => 'value',
      '#value' => $user,
    );
    $form['timestamp'] = array(
      '#type' => 'value',
      '#value' => $timestamp,
    );
    $form['help'] = array('#markup' => '<p>' . $this->t('This login can be used only once.') . '</p>');
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var $user \Drupal\user\UserInterface */
    $user = $form_state->getValue('user');
    user_login_finalize($user);
    $this->logger->notice('User %name used one-time login link at time %timestamp.', array('%name' => $user->getUsername(), '%timestamp' => $form_state->getValue('timestamp')));
    drupal_set_message($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
    // Let the user's password be changed without the current password check.
    $token = Crypt::randomBytesBase64(55);
    $_SESSION['pass_reset_' . $user->id()] = $token;
    $form_state->setRedirect(
      'entity.user.edit_form',
      array('user' => $user->id()),
      array(
        'query' => array('pass-reset-token' => $token),
        'absolute' => TRUE,
      )
    );
  }

}

