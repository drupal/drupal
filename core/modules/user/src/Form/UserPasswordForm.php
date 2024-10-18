<?php

namespace Drupal\user\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\user\UserNameValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user password reset form.
 *
 * Send the user an email to reset their password.
 *
 * @internal
 */
class UserPasswordForm extends FormBase implements WorkspaceSafeFormInterface {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Constructs a UserPasswordForm object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\user\UserNameValidator $userNameValidator
   *   The user validator service.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   */
  public function __construct(
    UserStorageInterface $user_storage,
    LanguageManagerInterface $language_manager,
    ConfigFactory $config_factory,
    FloodInterface $flood,
    protected UserNameValidator $userNameValidator,
    EmailValidatorInterface $email_validator,
  ) {
    $this->userStorage = $user_storage;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->flood = $flood;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('flood'),
      $container->get('user.name_validator'),
      $container->get('email.validator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_pass';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username or email address'),
      '#size' => 60,
      '#maxlength' => max(UserInterface::USERNAME_MAX_LENGTH, Email::EMAIL_MAX_LENGTH),
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
        'autocomplete' => 'username',
      ],
    ];
    // Allow logged in users to request this also.
    $user = $this->currentUser();
    if ($user->isAuthenticated()) {
      $form['name']['#type'] = 'value';
      $form['name']['#value'] = $user->getEmail();
      $form['mail'] = [
        '#prefix' => '<p>',
        '#markup' => $this->t('Password reset instructions will be mailed to %email. You must log out to use the password reset link in the email.', ['%email' => $user->getEmail()]),
        '#suffix' => '</p>',
      ];
    }
    else {
      $form['mail'] = [
        '#prefix' => '<p>',
        '#markup' => $this->t('Password reset instructions will be sent to your registered email address.'),
        '#suffix' => '</p>',
      ];
      $form['name']['#default_value'] = $this->getRequest()->query->get('name');
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['#cache']['contexts'][] = 'url.query_args';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $flood_config = $this->configFactory->get('user.flood');
    if (!$this->flood->isAllowed('user.password_request_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      $form_state->setErrorByName('name', $this->t('Too many password recovery requests from your IP address. It is temporarily blocked. Try again later or contact the site administrator.'));
      return;
    }
    $this->flood->register('user.password_request_ip', $flood_config->get('ip_window'));
    // First, see if the input is possibly valid as a username.
    $name = trim($form_state->getValue('name'));
    $violations = $this->userNameValidator->validateName($name);
    // Usernames have a maximum length shorter than email addresses. Only print
    // this error if the input is not valid as a username or email address.
    if ($violations->count() > 0 && !$this->emailValidator->isValid($name)) {
      $form_state->setErrorByName('name', $this->t("The username or email address is invalid."));
      return;
    }

    // Try to load by email.
    $users = $this->userStorage->loadByProperties(['mail' => $name]);
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(['name' => $name]);
    }
    $account = reset($users);
    // Blocked accounts cannot request a new password.
    if ($account && $account->id() && $account->isActive()) {
      // Register flood events based on the uid only, so they apply for any
      // IP address. This allows them to be cleared on successful reset (from
      // any IP).
      $identifier = $account->id();
      if (!$this->flood->isAllowed('user.password_request_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
        return;
      }
      $this->flood->register('user.password_request_user', $flood_config->get('user_window'), $identifier);
      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $form_state->getValue('account');
    if ($account) {
      // Mail one time login URL and instructions using current language.
      $mail = _user_mail_notify('password_reset', $account);
      if (!empty($mail)) {
        $this->logger('user')
          ->info('Password reset instructions mailed to %name at %email.', [
            '%name' => $account->getAccountName(),
            '%email' => $account->getEmail(),
          ]);
      }
    }
    else {
      $this->logger('user')
        ->info('Password reset form was submitted with an unknown or inactive account: %name.', [
          '%name' => $form_state->getValue('name'),
        ]);
    }
    // Make sure the status text is displayed even if no email was sent. This
    // message is deliberately the same as the success message for privacy.
    $this->messenger()
      ->addStatus($this->t('If %identifier is a valid account, an email will be sent with instructions to reset your password.', [
        '%identifier' => $form_state->getValue('name'),
      ]));

    $form_state->setRedirect('<front>');
  }

}
