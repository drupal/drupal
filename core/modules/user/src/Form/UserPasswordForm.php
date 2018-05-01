<?php

namespace Drupal\user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user password reset form.
 *
 * @internal
 */
class UserPasswordForm extends FormBase {

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
   * Constructs a UserPasswordForm object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(UserStorageInterface $user_storage, LanguageManagerInterface $language_manager) {
    $this->userStorage = $user_storage;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('language_manager')
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
      '#maxlength' => max(USERNAME_MAX_LENGTH, Email::EMAIL_MAX_LENGTH),
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
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
    $name = trim($form_state->getValue('name'));
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(['mail' => $name]);
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(['name' => $name]);
    }
    $account = reset($users);
    if ($account && $account->id()) {
      // Blocked accounts cannot request a new password.
      if (!$account->isActive()) {
        $form_state->setErrorByName('name', $this->t('%name is blocked or has not been activated yet.', ['%name' => $name]));
      }
      else {
        $form_state->setValueForElement(['#parents' => ['account']], $account);
      }
    }
    else {
      $form_state->setErrorByName('name', $this->t('%name is not recognized as a username or an email address.', ['%name' => $name]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $account = $form_state->getValue('account');
    // Mail one time login URL and instructions using current language.
    $mail = _user_mail_notify('password_reset', $account, $langcode);
    if (!empty($mail)) {
      $this->logger('user')->notice('Password reset instructions mailed to %name at %email.', ['%name' => $account->getUsername(), '%email' => $account->getEmail()]);
      $this->messenger()->addStatus($this->t('Further instructions have been sent to your email address.'));
    }

    $form_state->setRedirect('user.page');
  }

}
