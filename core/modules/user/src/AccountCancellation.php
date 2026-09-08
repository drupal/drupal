<?php

declare(strict_types=1);

namespace Drupal\user;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * User account cancellation service.
 */
class AccountCancellation {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly MessengerInterface $messenger,
    protected readonly NotificationHandler $notificationHandler,
    #[Autowire('@logger.channel.user')]
    protected readonly LoggerInterface $logger,
    protected readonly SessionInterface $session,
    /**
     * @var \Closure(): \Drupal\Core\Config\ConfigFactoryInterface
     */
    #[AutowireServiceClosure('config.factory')]
    protected \Closure $configFactoryClosure,
  ) {}

  /**
   * Cancel a user account.
   *
   * Since the user cancellation process needs to be run in a batch, either
   * Form API will invoke it, or batch_process() needs to be invoked after
   * calling this function and should define the path to redirect to.
   *
   * @param array $edit
   *   An array of submitted form values.
   * @param int $uid
   *   The user ID of the user account to cancel.
   * @param string $method
   *   The account cancellation method to use.
   *
   * @see static::cancelAccount()
   */
  public function cancel(array $edit, int $uid, string $method): void {
    $account = $this->entityTypeManager->getStorage('user')->load($uid);

    if (!$account) {
      $this->messenger->addError($this->t('The user account %id does not exist.', ['%id' => $uid]));
      $this->logger->error('Attempted to cancel non-existing user account: %id.', ['%id' => $uid]);
      return;
    }

    // Initialize batch (to set title).
    $batchBuilder = (new BatchBuilder())
      ->setTitle($this->t('Cancelling account'));
    batch_set($batchBuilder->toArray());

    // When the 'user_cancel_delete' method is used, user_delete() is called,
    // which invokes hook_ENTITY_TYPE_predelete() and hook_ENTITY_TYPE_delete()
    // for the user entity. Modules should use those hooks to respond to the
    // account deletion.
    if ($method != 'user_cancel_delete') {
      // Allow modules to add further sets to this batch.
      $this->moduleHandler->invokeAll('user_cancel', [$edit, $account, $method]);
    }

    // Finish the batch and actually cancel the account.
    $batchBuilder = (new BatchBuilder())
      ->setTitle($this->t('Cancelling user account'))
      ->addOperation(static::class . ':cancelAccount', [$edit, $account, $method]);

    // After cancelling account, ensure that user is logged out.
    if ($account->id() == $this->currentUser->id()) {
      // Batch API stores data in the session, so use the finished operation to
      // manipulate the current user's session id.
      $batchBuilder->setFinishCallback(static::class . ':regenerateSession');
    }

    batch_set($batchBuilder->toArray());

    // Batch processing is either handled via Form API or has to be invoked
    // manually.
  }

  /**
   * Implements callback_batch_operation().
   *
   * Last step for cancelling a user account.
   *
   * Since batch and session API require a valid user account, the actual
   * cancellation of a user account needs to happen last.
   *
   * @param array $edit
   *   An array of submitted form values.
   * @param \Drupal\user\UserInterface $account
   *   The user account to cancel.
   * @param string $method
   *   The account cancellation method to use.
   *
   * @see AccountCancellation::cancel()
   *
   * @internal
   */
  public function cancelAccount(array $edit, UserInterface $account, string $method): void {
    switch ($method) {
      case 'user_cancel_block':
      case 'user_cancel_block_unpublish':
      default:
        // Send account blocked notification if option was checked.
        if (!empty($edit['user_cancel_notify'])) {
          $this->notificationHandler->sendStatusBlocked($account);
        }
        $account->block();
        $account->save();
        $this->messenger->addStatus($this->t('Account %name has been disabled.', ['%name' => $account->getDisplayName()]));
        $this->logger->notice('Blocked user: %name %email.', [
          '%name' => $account->getAccountName(),
          '%email' => '<' . $account->getEmail() . '>',
        ]);
        break;

      case 'user_cancel_reassign':
      case 'user_cancel_delete':
        // Send account canceled notification if option was checked.
        if (!empty($edit['user_cancel_notify'])) {
          $this->notificationHandler->sendStatusCancelled($account);
        }
        $account->delete();
        $this->messenger->addStatus($this->t('Account %name has been deleted.', ['%name' => $account->getDisplayName()]));
        $this->logger->notice('Deleted user: %name %email.', [
          '%name' => $account->getAccountName(),
          '%email' => '<' . $account->getEmail() . '>',
        ]);
        break;
    }

    // After cancelling account, ensure that user is logged out. We can't
    // destroy their session though, as we might have information in it, and we
    // can't regenerate it because batch API uses the session ID, we will
    // regenerate it in static::regenerateSession().
    if ($account->id() == $this->currentUser->id()) {
      $this->currentUser->setAccount(new AnonymousUserSession());
    }
  }

  /**
   * Implements callback_batch_finished().
   *
   * Finished batch processing callback for cancelling a user account.
   *
   * @see AccountCancellation::cancel()
   *
   * @internal
   */
  public function regenerateSession(): void {
    // Regenerate the users session instead of calling session_destroy() as we
    // want to preserve any messages that might have been set.
    $this->session->migrate();
  }

  /**
   * Helper function to return available account cancellation methods.
   *
   * See documentation of hook_user_cancel_methods_alter().
   *
   * @return array
   *   An array containing all account cancellation methods as form elements.
   *
   * @see hook_user_cancel_methods_alter()
   * @see user_admin_settings()
   */
  public function cancelMethods(): array {
    $userSettings = ($this->configFactoryClosure)()->get('user.settings');
    $anonymous_name = $userSettings->get('anonymous');
    $methods = [
      'user_cancel_block' => [
        'title' => $this->t('Disable the account and keep its content.'),
        'description' => $this->t('Your account will be blocked and you will no longer be able to log in. All of your content will remain attributed to your username.'),
      ],
      'user_cancel_block_unpublish' => [
        'title' => $this->t('Disable the account and unpublish its content.'),
        'description' => $this->t('Your account will be blocked and you will no longer be able to log in. All of your content will be hidden from everyone but administrators.'),
      ],
      'user_cancel_reassign' => [
        'title' => $this->t('Delete the account and make its content belong to the %anonymous-name user. This action cannot be undone.', ['%anonymous-name' => $anonymous_name]),
        'description' => $this->t('Your account will be removed and all account information deleted. All of your content will be assigned to the %anonymous-name user.', ['%anonymous-name' => $anonymous_name]),
      ],
      'user_cancel_delete' => [
        'title' => $this->t('Delete the account and its content. This action cannot be undone.'),
        'description' => $this->t('Your account will be removed and all account information deleted. All of your content will also be deleted.'),
        'access' => $this->currentUser->hasPermission('administer users'),
      ],
    ];
    // Allow modules to customize account cancellation methods.
    $this->moduleHandler->alter('user_cancel_methods', $methods);

    // Turn all methods into real form elements.
    $form = [
      '#options' => [],
      '#default_value' => $userSettings->get('cancel_method'),
    ];
    foreach ($methods as $name => $method) {
      $form['#options'][$name] = $method['title'];
      // Add the description for the confirmation form. This description is
      // never shown for the cancel method option, only on the confirmation
      // form. Therefore, we use a custom #confirm_description property.
      if (isset($method['description'])) {
        $form[$name]['#confirm_description'] = $method['description'];
      }
      if (isset($method['access'])) {
        $form[$name]['#access'] = $method['access'];
      }
    }
    return $form;
  }

}
