<?php

namespace Drupal\user\Plugin\rest\resource;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceAccessTrait;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Represents user registration as a resource.
 */
#[RestResource(
  id: "user_registration",
  label: new TranslatableMarkup("User registration"),
  serialization_class: User::class,
  uri_paths: [
    "create" => "/user/register",
  ],
)]
class UserRegistrationResource extends ResourceBase {

  use EntityResourceValidationTrait;
  use EntityResourceAccessTrait;

  /**
   * User settings config instance.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $userSettings;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The password generator.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * Constructs a new UserRegistrationResource instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ImmutableConfig $user_settings
   *   A user settings config instance.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface|null $password_generator
   *   The password generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, ImmutableConfig $user_settings, AccountInterface $current_user, ?PasswordGeneratorInterface $password_generator = NULL) {
    if (is_null($password_generator)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $password_generator argument is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3405799', E_USER_DEPRECATED);
      $password_generator = \Drupal::service('password_generator');
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->userSettings = $user_settings;
    $this->currentUser = $current_user;
    $this->passwordGenerator = $password_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory')->get('user.settings'),
      $container->get('current_user'),
      $container->get('password_generator')
    );
  }

  /**
   * Responds to user registration POST request.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function post(?UserInterface $account = NULL) {
    $this->ensureAccountCanRegister($account);

    // Only activate new users if visitors are allowed to register.
    if ($this->userSettings->get('register') == UserInterface::REGISTER_VISITORS) {
      $account->activate();
    }
    else {
      $account->block();
    }

    // Generate password if email verification required.
    if ($this->userSettings->get('verify_mail')) {
      $account->setPassword($this->passwordGenerator->generate());
    }

    $this->checkEditFieldAccess($account);

    // Make sure that the user entity is valid (email and name are valid).
    $this->validate($account);

    // Create the account.
    $account->save();

    $this->sendEmailNotifications($account);

    return new ModifiedResourceResponse($account, 200);
  }

  /**
   * Ensure the account can be registered in this request.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to register.
   */
  protected function ensureAccountCanRegister(?UserInterface $account = NULL) {
    if ($account === NULL) {
      throw new BadRequestHttpException('No user account data for registration received.');
    }

    // POSTed user accounts must not have an ID set, because we always want to
    // create new entities here.
    if (!$account->isNew()) {
      throw new BadRequestHttpException('An ID has been set and only new user accounts can be registered.');
    }

    // Only allow anonymous users to register, authenticated users with the
    // necessary permissions can POST a new user to the "user" REST resource.
    // @see \Drupal\rest\Plugin\rest\resource\EntityResource
    if (!$this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException('Only anonymous users can register a user.');
    }

    // Verify that the current user can register a user account.
    if ($this->userSettings->get('register') == UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
      throw new AccessDeniedHttpException('You cannot register a new user account.');
    }

    if (!$this->userSettings->get('verify_mail')) {
      if (empty($account->getPassword())) {
        // If no email verification then the user must provide a password.
        throw new UnprocessableEntityHttpException('No password provided.');
      }
    }
    else {
      if (!empty($account->getPassword())) {
        // If email verification required then a password cannot provided.
        // The password will be set when the user logs in.
        throw new UnprocessableEntityHttpException('A Password cannot be specified. It will be generated on login.');
      }
    }
  }

  /**
   * Sends email notifications if necessary for user that was registered.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   */
  protected function sendEmailNotifications(UserInterface $account) {
    $approval_settings = $this->userSettings->get('register');
    // No email verification is required. Activating the user.
    if ($approval_settings == UserInterface::REGISTER_VISITORS) {
      if ($this->userSettings->get('verify_mail')) {
        // No administrator approval required.
        _user_mail_notify('register_no_approval_required', $account);
      }
    }
    // Administrator approval required.
    elseif ($approval_settings == UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) {
      _user_mail_notify('register_pending_approval', $account);
    }
  }

}
