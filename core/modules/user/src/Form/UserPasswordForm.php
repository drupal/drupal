<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserPasswordForm.
 */

namespace Drupal\user\Form;

use Drupal\Core\Field\Plugin\Field\FieldType\EmailItem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\user\UserStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user password reset form.
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
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username or email address'),
      '#size' => 60,
      '#maxlength' => max(USERNAME_MAX_LENGTH, Email::EMAIL_MAX_LENGTH),
      '#required' => TRUE,
      '#attributes' => array(
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ),
    );
    // Allow logged in users to request this also.
    $user = $this->currentUser();
    if ($user->isAuthenticated()) {
      $form['name']['#type'] = 'value';
      $form['name']['#value'] = $user->getEmail();
      $form['mail'] = array(
        '#prefix' => '<p>',
        '#markup' =>  $this->t('Password reset instructions will be mailed to %email. You must log out to use the password reset link in the email.', array('%email' => $user->getEmail())),
        '#suffix' => '</p>',
      );
    }
    else {
      $form['name']['#default_value'] = $this->getRequest()->query->get('name');
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Email new password'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = trim($form_state->getValue('name'));
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(array('mail' => $name, 'status' => '1'));
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(array('name' => $name, 'status' => '1'));
    }
    $account = reset($users);
    if ($account && $account->id()) {
      form_set_value(array('#parents' => array('account')), $account, $form_state);
    }
    else {
      $form_state->setErrorByName('name', $this->t('Sorry, %name is not recognized as a username or an email address.', array('%name' => $name)));
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
      $this->logger('user')->notice('Password reset instructions mailed to %name at %email.', array('%name' => $account->getUsername(), '%email' => $account->getEmail()));
      drupal_set_message($this->t('Further instructions have been sent to your email address.'));
    }

    $form_state->setRedirect('user.page');
  }

}
