<?php

namespace Drupal\user;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
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
    $form['#cache']['tags'] = $config->getCacheTags();

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
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
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
      if (!$form_state->get('user_pass_reset') && ($token = $this->getRequest()->get('pass-reset-token'))) {
        $session_key = 'pass_reset_' . $account->id();
        $user_pass_reset = isset($_SESSION[$session_key]) && Crypt::hashEquals($_SESSION[$session_key], $token);
        $form_state->set('user_pass_reset', $user_pass_reset);
      }

      // The user must enter their current password to change to a new one.
      if ($user->id() == $account->id()) {
        $form['account']['current_pass'] = array(
          '#type' => 'password',
          '#title' => $this->t('Current password'),
          '#size' => 25,
          '#access' => !$form_state->get('user_pass_reset'),
          '#weight' => -5,
          // Do not let web browsers remember this password, since we are
          // trying to confirm that the person submitting the form actually
          // knows the current one.
          '#attributes' => array('autocomplete' => 'off'),
        );
        $form_state->set('user', $account);

        // The user may only change their own password without their current
        // password if they logged in via a one-time login link.
        if (!$form_state->get('user_pass_reset')) {
          $form['account']['current_pass']['#description'] = $this->t('Required if you want to change the %mail or %pass below. <a href=":request_new_url" title="Send password reset instructions via email.">Reset your password</a>.', array(
            '%mail' => $form['account']['mail']['#title'],
            '%pass' => $this->t('Password'),
            ':request_new_url' => $this->url('user.pass'),
          ));
        }
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

    if ($admin || !$register) {
      $status = $account->get('status')->value;
    }
    else {
      $status = $config->get('register') == USER_REGISTER_VISITORS ? 1 : 0;
    }

    $form['account']['status'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#default_value' => $status,
      '#options' => array($this->t('Blocked'), $this->t('Active')),
      '#access' => $admin,
    );

    $roles = array_map(array('\Drupal\Component\Utility\Html', 'escape'), user_role_names(TRUE));

    $form['account']['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => (!$register ? $account->getRoles() : array()),
      '#options' => $roles,
      '#access' => $roles && $user->hasPermission('administer permissions'),
    );

    // Special handling for the inevitable "Authenticated user" role.
    $form['account']['roles'][RoleInterface::AUTHENTICATED_ID] = array(
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    );

    $form['account']['notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user of new account'),
      '#access' => $register && $admin,
    );

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
    $user->getUntranslated()->langcode = $user->preferred_langcode;
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

    // Translate the empty value '' of language selects to an unset field.
    foreach (array('preferred_langcode', 'preferred_admin_langcode') as $field_name) {
      if ($form_state->getValue($field_name) === '') {
        $account->$field_name = NULL;
      }
    }

    // Set existing password if set in the form state.
    $current_pass = trim($form_state->getValue('current_pass'));
    if (strlen($current_pass) > 0) {
      $account->setExistingPassword($current_pass);
    }

    // Skip the protected user field constraint if the user came from the
    // password recovery page.
    $account->_skipProtectedUserFieldConstraint = $form_state->get('user_pass_reset');

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_merge(array(
      'name',
      'pass',
      'mail',
      'timezone',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode'
    ), parent::getEditedFieldNames($form_state));
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    $field_names = array(
      'name',
      'pass',
      'mail',
      'timezone',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode'
    );
    foreach ($violations->getByFields($field_names) as $violation) {
      list($field_name) = explode('.', $violation->getPropertyPath(), 2);
      $form_state->setErrorByName($field_name, $violation->getMessage());
    }
    parent::flagViolations($violations, $form, $form_state);
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
