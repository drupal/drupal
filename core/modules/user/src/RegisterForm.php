<?php

/**
 * @file
 * Definition of Drupal\user\RegisterForm.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form controller for the user register forms.
 */
class RegisterForm extends AccountForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManager $language_manager, QueryFactory $entity_query) {
    parent::__construct($entity_manager, $language_manager, $entity_query);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entity;
    $admin = $user->hasPermission('administer users');
    // Pass access information to the submit handler. Running an access check
    // inside the submit function interferes with form processing and breaks
    // hook_form_alter().
    $form['administer_users'] = array(
      '#type' => 'value',
      '#value' => $admin,
    );

    // If we aren't admin but already logged on, go to the user page instead.
    if (!$admin && $user->isAuthenticated()) {
      return new RedirectResponse(url('user/' . \Drupal::currentUser()->id(), array('absolute' => TRUE)));
    }

    $form['#attached']['library'][] = 'core/drupal.form';
    $form['#attributes']['data-user-info-from-browser'] = TRUE;

    // Because the user status has security implications, users are blocked by
    // default when created programmatically and need to be actively activated
    // if needed. When administrators create users from the user interface,
    // however, we assume that they should be created as activated by default.
    if ($admin) {
      $account->activate();
    }

    // Start with the default user account fields.
    $form = parent::form($form, $form_state, $account);

    if ($admin) {
      // Redirect back to page which initiated the create request; usually
      // admin/people/create.
      $form_state['redirect'] = current_path();
    }

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::actions().
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#value'] = $this->t('Create new account');
    return $element;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $admin = $form_state['values']['administer_users'];

    if (!\Drupal::config('user.settings')->get('verify_mail') || $admin) {
      $pass = $form_state['values']['pass'];
    }
    else {
      $pass = user_password();
    }

    // Remove unneeded values.
    form_state_values_clean($form_state);

    $form_state['values']['pass'] = $pass;
    $form_state['values']['init'] = $form_state['values']['mail'];

    parent::submit($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $account = $this->entity;
    $pass = $account->getPassword();
    $admin = $form_state['values']['administer_users'];
    $notify = !empty($form_state['values']['notify']);

    // Save has no return value so this cannot be tested.
    // Assume save has gone through correctly.
    $account->save();

    $form_state['user'] = $account;
    $form_state['values']['uid'] = $account->id();

    $this->logger('user')->notice('New user: %name %email.', array('%name' => $form_state['values']['name'], '%email' => '<' . $form_state['values']['mail'] . '>', 'type' => l($this->t('Edit'), 'user/' . $account->id() . '/edit')));

    // Add plain text password into user account to generate mail tokens.
    $account->password = $pass;

    // New administrative account without notification.
    if ($admin && !$notify) {
      drupal_set_message($this->t('Created a new user account for <a href="@url">%name</a>. No email has been sent.', array('@url' => $account->url(), '%name' => $account->getUsername())));
    }
    // No email verification required; log in user immediately.
    elseif (!$admin && !\Drupal::config('user.settings')->get('verify_mail') && $account->isActive()) {
      _user_mail_notify('register_no_approval_required', $account);
      user_login_finalize($account);
      drupal_set_message($this->t('Registration successful. You are now logged in.'));
      $form_state->setRedirect('<front>');
    }
    // No administrator approval required.
    elseif ($account->isActive() || $notify) {
      if (!$account->getEmail() && $notify) {
        drupal_set_message($this->t('The new user <a href="@url">%name</a> was created without an email address, so no welcome message was sent.', array('@url' => $account->url(), '%name' => $account->getUsername())));
      }
      else {
        $op = $notify ? 'register_admin_created' : 'register_no_approval_required';
        if (_user_mail_notify($op, $account)) {
          if ($notify) {
            drupal_set_message($this->t('A welcome message with further instructions has been emailed to the new user <a href="@url">%name</a>.', array('@url' => $account->url(), '%name' => $account->getUsername())));
          }
          else {
            drupal_set_message($this->t('A welcome message with further instructions has been sent to your email address.'));
            $form_state->setRedirect('<front>');
          }
        }
      }
    }
    // Administrator approval required.
    else {
      _user_mail_notify('register_pending_approval', $account);
      drupal_set_message($this->t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.<br />In the meantime, a welcome message with further instructions has been sent to your email address.'));
      $form_state->setRedirect('<front>');
    }
  }
}
