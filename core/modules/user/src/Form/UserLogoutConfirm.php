<?php

declare(strict_types=1);

namespace Drupal\user\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for user logout.
 */
class UserLogoutConfirm extends ConfirmFormBase implements WorkspaceSafeFormInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Log out');
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
    user_logout();
    $form_state->setRedirect('<front>');
  }

}
