<?php

/**
 * @file
 * Contains \Drupal\user\AccountForm.
 */

namespace Drupal\user;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUser;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the user account forms.
 */
abstract class AccountForm extends ContentEntityForm {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a new EntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory
   *   The entity query factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, QueryFactory $entity_query) {
    parent::__construct($entity_manager);
    $this->languageManager = $language_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entity;
    $user = $this->currentUser();
    $config = \Drupal::config('user.settings');

    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $register = $account->isAnonymous();
    $admin = $user->hasPermission('administer users');

    // Account information.
    $form['account'] = array(
      '#type'   => 'container',
      '#weight' => -10,
    );

    // The mail field is NOT required if account originally had no mail set
    // and the user performing the edit has 'administer users' permission.
    // This allows users without email address to be edited and deleted.
    // Also see \Drupal\user\Plugin\Validation\Constraint\UserMailRequired.
    $form['account']['mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.'),
      '#required' => !(!$account->getEmail() && $user->hasPermission('administer users')),
      '#default_value' => (!$register ? $account->getEmail() : ''),
    );

    // Only show name field on registration form or user can change own username.
    $form['account']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t('Spaces are allowed; punctuation is not allowed except for periods, hyphens, apostrophes, and underscores.'),
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array('username'),
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ),
      '#default_value' => (!$register ? $account->getUsername() : ''),
      '#access' => ($register || ($user->id() == $account->id() && $user->hasPermission('change own username')) || $admin),
    );

    // Display password field only for existing users or when user is allowed to
    // assign a password during registration.
    if (!$register) {
      $form['account']['pass'] = array(
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => $this->t('To change the current user password, enter the new password in both fields.'),
      );

      // To skip the current password field, the user must have logged in via a
      // one-time link and have the token in the URL. Store this in $form_state
      // so it persists even on subsequent Ajax requests.
      if (!$form_state->get('user_pass_reset')) {
        $user_pass_reset = $pass_reset = isset($_SESSION['pass_reset_' . $account->id()]) && (\Drupal::request()->query->get('pass-reset-token') == $_SESSION['pass_reset_' . $account->id()]);
        $form_state->set('user_pass_reset', $user_pass_reset);
      }

      $protected_values = array();
      $current_pass_description = '';

      // The user may only change their own password without their current
      // password if they logged in via a one-time login link.
      if (!$form_state->get('user_pass_reset')) {
        $protected_values['mail'] = $form['account']['mail']['#title'];
        $protected_values['pass'] = $this->t('Password');
        $request_new = $this->l($this->t('Reset your password'), new Url('user.pass',
          array(), array('attributes' => array('title' => $this->t('Send password reset instructions via e-mail.'))))
        );
        $current_pass_description = $this->t('Required if you want to change the %mail or %pass below. !request_new.',
          array(
            '%mail' => $protected_values['mail'],
            '%pass' => $protected_values['pass'],
            '!request_new' => $request_new,
          )
        );
      }

      // The user must enter their current password to change to a new one.
      if ($user->id() == $account->id()) {
        $form['account']['current_pass_required_values'] = array(
          '#type' => 'value',
          '#value' => $protected_values,
        );

        $form['account']['current_pass'] = array(
          '#type' => 'password',
          '#title' => $this->t('Current password'),
          '#size' => 25,
          '#access' => !empty($protected_values),
          '#description' => $current_pass_description,
          '#weight' => -5,
          // Do not let web browsers remember this password, since we are
          // trying to confirm that the person submitting the form actually
          // knows the current one.
          '#attributes' => array('autocomplete' => 'off'),
        );

        $form_state->set('user', $account);
        $form['#validate'][] = 'user_validate_current_pass';
      }
    }
    elseif (!$config->get('verify_mail') || $admin) {
      $form['account']['pass'] = array(
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => $this->t('Provide a password for the new account in both fields.'),
        '#required' => TRUE,
      );
    }

    // When not building the user registration form, prevent web browsers from
    // autofilling/prefilling the email, username, and password fields.
    if ($this->getOperation() != 'register') {
      foreach (array('mail', 'name', 'pass') as $key) {
        if (isset($form['account'][$key])) {
          $form['account'][$key]['#attributes']['autocomplete'] = 'off';
        }
      }
    }

    if ($admin) {
      $status = $account->isActive();
    }
    else {
      $status = $register ? $config->get('register') == USER_REGISTER_VISITORS : $account->isActive();
    }

    $form['account']['status'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#default_value' => $status,
      '#options' => array($this->t('Blocked'), $this->t('Active')),
      '#access' => $admin,
    );

    $roles = array_map(array('\Drupal\Component\Utility\String', 'checkPlain'), user_role_names(TRUE));

    $form['account']['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => (!$register ? $account->getRoles() : array()),
      '#options' => $roles,
      '#access' => $roles && $user->hasPermission('administer permissions'),
    );

    // Special handling for the inevitable "Authenticated user" role.
    $form['account']['roles'][DRUPAL_AUTHENTICATED_RID] = array(
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    );

    $form['account']['notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user of new account'),
      '#access' => $register && $admin,
    );

    // Signature.
    $form['signature_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Signature settings'),
      '#open' => TRUE,
      '#weight' => 1,
      '#access' => (!$register && $config->get('signatures')),
    );
    // While the details group will simply not be rendered if empty, the actual
    // signature element cannot use #access, since #type 'text_format' is not
    // available when Filter module is not installed. If the user account has an
    // existing signature value and format, then the existing field values will
    // just be re-saved to the database in case of an entity update.
    if ($this->moduleHandler->moduleExists('filter')) {
      $form['signature_settings']['signature'] = array(
        '#type' => 'text_format',
        '#title' => $this->t('Signature'),
        '#default_value' => $account->getSignature(),
        '#description' => $this->t('Your signature will be publicly displayed at the end of your comments.'),
        '#format' => $account->getSignatureFormat(),
      );
    }

    $user_preferred_langcode = $register ? $language_interface->getId() : $account->getPreferredLangcode();

    $user_preferred_admin_langcode = $register ? $language_interface->getId() : $account->getPreferredAdminLangcode(FALSE);

    // Is the user preferred language added?
    $user_language_added = FALSE;
    if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      $negotiator = $this->languageManager->getNegotiator();
      $user_language_added = $negotiator && $negotiator->isNegotiationMethodEnabled(LanguageNegotiationUser::METHOD_ID, LanguageInterface::TYPE_INTERFACE);
    }
    $form['language'] = array(
      '#type' => $this->languageManager->isMultilingual() ? 'details' : 'container',
      '#title' => $this->t('Language settings'),
      '#open' => TRUE,
      // Display language selector when either creating a user on the admin
      // interface or editing a user account.
      '#access' => !$register || $user->hasPermission('administer users'),
    );

    $form['language']['preferred_langcode'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('Site language'),
      '#languages' => LanguageInterface::STATE_CONFIGURABLE,
      '#default_value' => $user_preferred_langcode,
      '#description' => $user_language_added ? $this->t("This account's preferred language for emails and site presentation.") : $this->t("This account's preferred language for emails."),
      // This is used to explain that user preferred language and entity
      // language are synchronized. It can be removed if a different behavior is
      // desired.
      '#pre_render' => ['user_langcode' => [$this, 'alterPreferredLangcodeDescription']],
    );

    // Only show the account setting for Administration pages language to users
    // if one of the detection and selection methods uses it.
    $show_admin_language = FALSE;
    if ($account->hasPermission('access administration pages') && $this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      $negotiator = $this->languageManager->getNegotiator();
      $show_admin_language = $negotiator && $negotiator->isNegotiationMethodEnabled(LanguageNegotiationUserAdmin::METHOD_ID);
    }
    $form['language']['preferred_admin_langcode'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('Administration pages language'),
      '#languages' => LanguageInterface::STATE_CONFIGURABLE,
      '#default_value' => $user_preferred_admin_langcode,
      '#access' => $show_admin_language,
      '#empty_option' => $this->t('- No preference -'),
      '#empty_value' => '',
    );

    // User entities contain both a langcode property (for identifying the
    // language of the entity data) and a preferred_langcode property (see
    // above). Rather than provide a UI forcing the user to choose both
    // separately, assume that the user profile data is in the user's preferred
    // language. This entity builder provides that synchronization. For
    // use-cases where this synchronization is not desired, a module can alter
    // or remove this item.
    $form['#entity_builders']['sync_user_langcode'] = [$this, 'syncUserLangcode'];

    return parent::form($form, $form_state, $account);
  }

  /**
   * Alters the preferred language widget description.
   *
   * @param array $element
   *   The preferred language form element.
   *
   * @return array
   *   The preferred language form element.
   */
  public function alterPreferredLangcodeDescription(array $element) {
    // Only add to the description if the form element has a description.
    if (isset($element['#description'])) {
      $element['#description'] .= ' ' . $this->t("This is also assumed to be the primary language of this account's profile information.");
    }
    return $element;
  }

  /**
   * Synchronizes preferred language and entity language.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\user\UserInterface $user
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function syncUserLangcode($entity_type_id, UserInterface $user, array &$form, FormStateInterface &$form_state) {
    $user->langcode = $user->preferred_langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    // Change the roles array to a list of enabled roles.
    // @todo: Alter the form state as the form values are directly extracted and
    //   set on the field, which throws an exception as the list requires
    //   numeric keys. Allow to override this per field. As this function is
    //   called twice, we have to prevent it from getting the array keys twice.

    if (is_string(key($form_state->getValue('roles')))) {
      $form_state->setValue('roles', array_keys(array_filter($form_state->getValue('roles'))));
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = parent::buildEntity($form, $form_state);

    // Take care of mapping signature form element values as their structure
    // does not directly match the field structure.
    $signature = $form_state->getValue('signature');
    $account->setSignature($signature['value']);
    $account->setSignatureFormat($signature['format']);

    // Translate the empty value '' of language selects to an unset field.
    foreach (array('preferred_langcode', 'preferred_admin_langcode') as $field_name) {
      if ($form_state->getValue($field_name) === '') {
        $account->$field_name = NULL;
      }
    }
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $account */
    $account = parent::validate($form, $form_state);

    // Customly trigger validation of manually added fields and add in
    // violations. This is necessary as entity form displays only invoke entity
    // validation for fields contained in the display.
    $field_names = array(
      'name',
      'mail',
      'signature',
      'signature_format',
      'timezone',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode'
    );
    foreach ($field_names as $field_name) {
      $violations = $account->$field_name->validate();
      foreach ($violations as $violation) {
        $form_state->setErrorByName($field_name, $violation->getMessage());
      }
    }

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $user = $this->getEntity($form_state);
    // If there's a session set to the users id, remove the password reset tag
    // since a new password was saved.
    if (isset($_SESSION['pass_reset_'. $user->id()])) {
      unset($_SESSION['pass_reset_'. $user->id()]);
    }
  }
}
