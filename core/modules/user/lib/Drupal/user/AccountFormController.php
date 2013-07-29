<?php

/**
 * @file
 * Definition of Drupal\user\AccountFormController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityFormControllerNG;
use Drupal\Core\Language\Language;

/**
 * Form controller for the user account forms.
 */
abstract class AccountFormController extends EntityFormControllerNG {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $account = $this->entity;
    global $user;
    $config = config('user.settings');

    $language_interface = language(Language::TYPE_INTERFACE);
    $register = $account->isAnonymous();
    $admin = user_access('administer users');

    // Account information.
    $form['account'] = array(
      '#type'   => 'container',
      '#weight' => -10,
    );

    // Only show name field on registration form or user can change own username.
    $form['account']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => t('Spaces are allowed; punctuation is not allowed except for periods, hyphens, apostrophes, and underscores.'),
      '#required' => TRUE,
      '#attributes' => array('class' => array('username'), 'autocorrect' => 'off', 'autocomplete' => 'off', 'autocapitalize' => 'off',
      'spellcheck' => 'false'),
      '#default_value' => (!$register ? $account->getUsername() : ''),
      '#access' => ($register || ($user->id() == $account->id() && user_access('change own username')) || $admin),
      '#weight' => -10,
    );

    // The mail field is NOT required if account originally had no mail set
    // and the user performing the edit has 'administer users' permission.
    // This allows users without e-mail address to be edited and deleted.
    $form['account']['mail'] = array(
      '#type' => 'email',
      '#title' => t('E-mail address'),
      '#description' => t('A valid e-mail address. All e-mails from the system will be sent to this address. The e-mail address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail.'),
      '#required' => !(!$account->getEmail() && user_access('administer users')),
      '#default_value' => (!$register ? $account->getEmail() : ''),
      '#attributes' => array('autocomplete' => 'off'),
    );

    // Display password field only for existing users or when user is allowed to
    // assign a password during registration.
    if (!$register) {
      $form['account']['pass'] = array(
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => t('To change the current user password, enter the new password in both fields.'),
      );

      // To skip the current password field, the user must have logged in via a
      // one-time link and have the token in the URL.
      $pass_reset = isset($_SESSION['pass_reset_' . $account->id()]) && isset($_GET['pass-reset-token']) && ($_GET['pass-reset-token'] == $_SESSION['pass_reset_' . $account->id()]);
      $protected_values = array();
      $current_pass_description = '';

      // The user may only change their own password without their current
      // password if they logged in via a one-time login link.
      if (!$pass_reset) {
        $protected_values['mail'] = $form['account']['mail']['#title'];
        $protected_values['pass'] = t('Password');
        $request_new = l(t('Request new password'), 'user/password', array('attributes' => array('title' => t('Request new password via e-mail.'))));
        $current_pass_description = t('Required if you want to change the %mail or %pass below. !request_new.', array('%mail' => $protected_values['mail'], '%pass' => $protected_values['pass'], '!request_new' => $request_new));
      }

      // The user must enter their current password to change to a new one.
      if ($user->id() == $account->id()) {
        $form['account']['current_pass_required_values'] = array(
          '#type' => 'value',
          '#value' => $protected_values,
        );

        $form['account']['current_pass'] = array(
          '#type' => 'password',
          '#title' => t('Current password'),
          '#size' => 25,
          '#access' => !empty($protected_values),
          '#description' => $current_pass_description,
          '#weight' => -5,
          // Do not let web browsers remember this password, since we are
          // trying to confirm that the person submitting the form actually
          // knows the current one.
          '#attributes' => array('autocomplete' => 'off'),
        );

        $form_state['user'] = $account;
        $form['#validate'][] = 'user_validate_current_pass';
      }
    }
    elseif (!$config->get('verify_mail') || $admin) {
      $form['account']['pass'] = array(
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => t('Provide a password for the new account in both fields.'),
        '#required' => TRUE,
      );
    }

    if ($admin) {
      $status = $account->isActive();
    }
    else {
      $status = $register ? $config->get('register') == USER_REGISTER_VISITORS : $account->isActive();
    }

    $form['account']['status'] = array(
      '#type' => 'radios',
      '#title' => t('Status'),
      '#default_value' => $status,
      '#options' => array(t('Blocked'), t('Active')),
      '#access' => $admin,
    );

    $roles = array_map('check_plain', user_role_names(TRUE));
    // The disabled checkbox subelement for the 'authenticated user' role
    // must be generated separately and added to the checkboxes element,
    // because of a limitation in Form API not supporting a single disabled
    // checkbox within a set of checkboxes.
    // @todo This should be solved more elegantly. See issue #119038.
    $checkbox_authenticated = array(
      '#type' => 'checkbox',
      '#title' => $roles[DRUPAL_AUTHENTICATED_RID],
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    );
    unset($roles[DRUPAL_AUTHENTICATED_RID]);

    $form['account']['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#default_value' => (!$register ? $account->getRoles() : array()),
      '#options' => $roles,
      '#access' => $roles && user_access('administer permissions'),
      DRUPAL_AUTHENTICATED_RID => $checkbox_authenticated,
    );

    $form['account']['notify'] = array(
      '#type' => 'checkbox',
      '#title' => t('Notify user of new account'),
      '#access' => $register && $admin,
    );

    // Signature.
    $form['signature_settings'] = array(
      '#type' => 'details',
      '#title' => t('Signature settings'),
      '#weight' => 1,
      '#access' => (!$register && $config->get('signatures')),
    );

    $form['signature_settings']['signature'] = array(
      '#type' => 'text_format',
      '#title' => t('Signature'),
      '#default_value' => $account->getSignature(),
      '#description' => t('Your signature will be publicly displayed at the end of your comments.'),
      '#format' => $account->getSignatureFormat(),
    );

    $user_preferred_langcode = $register ? $language_interface->id : $account->getPreferredLangcode();

    $user_preferred_admin_langcode = $register ? $language_interface->id : $account->getPreferredAdminLangcode();

    // Is default the interface language?
    include_once DRUPAL_ROOT . '/core/includes/language.inc';
    $interface_language_is_default = language_negotiation_method_get_first(Language::TYPE_INTERFACE) != LANGUAGE_NEGOTIATION_SELECTED;
    $form['language'] = array(
      '#type' => language_multilingual() ? 'details' : 'container',
      '#title' => t('Language settings'),
      // Display language selector when either creating a user on the admin
      // interface or editing a user account.
      '#access' => !$register || user_access('administer users'),
    );

    $form['language']['preferred_langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Site language'),
      '#languages' => Language::STATE_CONFIGURABLE,
      '#default_value' => $user_preferred_langcode,
      '#description' => $interface_language_is_default ? t("This account's preferred language for e-mails and site presentation.") : t("This account's preferred language for e-mails."),
    );

    $form['language']['preferred_admin_langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Administration pages language'),
      '#languages' => Language::STATE_CONFIGURABLE,
      '#default_value' => $user_preferred_admin_langcode,
      '#access' => user_access('access administration pages', $account),
    );

    // User entities contain both a langcode property (for identifying the
    // language of the entity data) and a preferred_langcode property (see
    // above). Rather than provide a UI forcing the user to choose both
    // separately, assume that the user profile data is in the user's preferred
    // language. This element provides that synchronization. For use-cases where
    // this synchronization is not desired, a module can alter or remove this
    // element.
    $form['language']['langcode'] = array(
      '#type' => 'value',
      '#value_callback' => '_user_language_selector_langcode_value',
      // For the synchronization to work, this element must have a larger weight
      // than the preferred_langcode element. Set a large weight here in case
      // a module alters the weight of the other element.
      '#weight' => 100,
    );

    return parent::form($form, $form_state, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, array &$form_state) {
    // Change the roles array to a list of enabled roles.
    // @todo: Alter the form state as the form values are directly extracted and
    //   set on the field, which throws an exception as the list requires
    //   numeric keys. Allow to override this per field. As this function is
    //   called twice, we have to prevent it from getting the array keys twice.

    if (is_string(key($form_state['values']['roles']))) {
      $form_state['values']['roles'] = array_keys(array_filter($form_state['values']['roles']));
    }
    return parent::buildEntity($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    $account = $this->entity;
    // Validate new or changing username.
    if (isset($form_state['values']['name'])) {
      if ($error = user_validate_name($form_state['values']['name'])) {
        form_set_error('name', $error);
      }
      // Cast the user ID as an integer. It might have been set to NULL, which
      // could lead to unexpected results.
      else {
        $name_taken = (bool) db_select('users')
        ->fields('users', array('uid'))
        ->condition('uid', (int) $account->id(), '<>')
        ->condition('name', db_like($form_state['values']['name']), 'LIKE')
        ->range(0, 1)
        ->execute()
        ->fetchField();

        if ($name_taken) {
          form_set_error('name', t('The name %name is already taken.', array('%name' => $form_state['values']['name'])));
        }
      }
    }

    $mail = $form_state['values']['mail'];

    if (!empty($mail)) {
      $mail_taken = (bool) db_select('users')
      ->fields('users', array('uid'))
      ->condition('uid', (int) $account->id(), '<>')
      ->condition('mail', db_like($mail), 'LIKE')
      ->range(0, 1)
      ->execute()
      ->fetchField();

      if ($mail_taken) {
        // Format error message dependent on whether the user is logged in or not.
        if ($GLOBALS['user']->isAuthenticated()) {
          form_set_error('mail', t('The e-mail address %email is already taken.', array('%email' => $mail)));
        }
        else {
          form_set_error('mail', t('The e-mail address %email is already registered. <a href="@password">Have you forgotten your password?</a>', array('%email' => $mail, '@password' => url('user/password'))));
        }
      }
    }

    // Make sure the signature isn't longer than the size of the database field.
    // Signatures are disabled by default, so make sure it exists first.
    if (isset($form_state['values']['signature'])) {
      // Move text format for user signature into 'signature_format'.
      $form_state['values']['signature_format'] = $form_state['values']['signature']['format'];
      // Move text value for user signature into 'signature'.
      $form_state['values']['signature'] = $form_state['values']['signature']['value'];

      $user_schema = drupal_get_schema('users');
      if (drupal_strlen($form_state['values']['signature']) > $user_schema['fields']['signature']['length']) {
        form_set_error('signature', t('The signature is too long: it must be %max characters or less.', array('%max' => $user_schema['fields']['signature']['length'])));
      }
    }
  }
}
