<?php

declare(strict_types=1);

namespace Drupal\user\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\LogoutFinalizer;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides a confirmation form for user logout.
 */
#[Route(
  path: '/user/logout/confirm',
  name: 'user.logout.confirm',
  requirements: [
    '_user_is_logged_in' => 'TRUE',
  ],
  defaults: [
    '_title' => new TranslatableMarkup('Log out'),
  ],
)]
class UserLogoutConfirm extends ConfirmFormBase implements WorkspaceSafeFormInterface {

  /**
   * The user logout finalizer.
   */
  protected LogoutFinalizer $logoutFinalizer;

  public function __construct(
    ?LogoutFinalizer $logoutFinalizer = NULL,
  ) {
    if ($logoutFinalizer === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $logoutFinalizer argument is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. See https://www.drupal.org/node/3379194', E_USER_DEPRECATED);
      $logoutFinalizer = \Drupal::service(LogoutFinalizer::class);
    }
    $this->logoutFinalizer = $logoutFinalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Log out');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // phpcs:ignore Drupal.Semantics.FunctionT.EmptyString
    return $this->t('');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to log out?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'user_logout_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->logoutFinalizer->finalizeLogout();
    $form_state->setRedirect('<front>');
  }

}
