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
use Drupal\user\NotificationHandler;
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
   * The user notification handler.
   */
  protected NotificationHandler $notificationHandler;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected ImmutableConfig $userSettings,
    protected AccountInterface $currentUser,
    protected PasswordGeneratorInterface $passwordGenerator,
    ?NotificationHandler $notification_handler = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    if ($notification_handler === NULL) {
      @trigger_error('Calling ' . __CLASS__ . ' constructor without the $notificationHandler argument is deprecated in drupal:11.5.0 and it will be required in drupal:13.0.0. See https://www.drupal.org/node/3539363', E_USER_DEPRECATED);
    }
    $this->notificationHandler = $notification_handler ?? \Drupal::service(NotificationHandler::class);
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
      $container->get('password_generator'),
      $container->get(NotificationHandler::class),
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
      // No administrator approval required.
      $this->notificationHandler->sendRegisterNoApprovalRequired($account);
    }
    // Administrator approval required.
    elseif ($approval_settings == UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) {
      $this->notificationHandler->sendRegisterPendingApproval($account);
    }
  }

}
