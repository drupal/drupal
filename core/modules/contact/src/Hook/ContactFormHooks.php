<?php

namespace Drupal\contact\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserDataInterface;

/**
 * Form hook implementations for Contact module.
 */
class ContactFormHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly AccountInterface $currentUser,
    protected readonly UserDataInterface $userData,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_form_FORM_ID_alter() for \Drupal\user\ProfileForm.
   *
   * Add the enable personal contact form to an individual user's account page.
   *
   * @see \Drupal\user\ProfileForm::form()
   */
  #[Hook('form_user_form_alter')]
  public function formUserFormAlter(&$form, FormStateInterface $form_state) : void {
    $form['contact'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact settings'),
      '#open' => TRUE,
      '#weight' => 5,
    ];
    $account = $form_state->getFormObject()->getEntity();
    if (!$this->currentUser->isAnonymous() && $account->id()) {
      $account_data = $this->userData->get('contact', $account->id(), 'enabled');
    }
    $form['contact']['contact'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Personal contact form'),
      '#default_value' => $account_data ?? $this->configFactory->getEditable('contact.settings')->get('user_default_enabled'),
      '#description' => $this->t('Allow other users to contact you via a personal contact form which keeps your email address hidden. Note that some privileged users such as site administrators are still able to contact you even if you choose to disable this feature.'),
    ];
    $form['actions']['submit']['#submit'][] = self::class . ':profileFormSubmit';
  }

  /**
   * Provides an additional submit handler for \Drupal\user\ProfileForm form.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   */
  public function profileFormSubmit(array $form, FormStateInterface $form_state): void {
    $account = $form_state->getFormObject()->getEntity();
    if ($account->id() && $form_state->hasValue('contact')) {
      $this->userData->set('contact', $account->id(), 'enabled', (int) $form_state->getValue('contact'));
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for \Drupal\user\AccountSettingsForm.
   *
   * Adds the default personal contact setting on the user settings page.
   */
  #[Hook('form_user_admin_settings_alter')]
  public function formUserAdminSettingsAlter(&$form, FormStateInterface $form_state) : void {
    $form['contact'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact settings'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['contact']['contact_default_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the personal contact form by default for new users'),
      '#description' => $this->t('Changing this setting will not affect existing users.'),
      '#default_value' => $this->configFactory->getEditable('contact.settings')->get('user_default_enabled'),
    ];
    // Add submit handler to save contact configuration. Note that, in this
    // case, it's not possible to simply use the #config_target key. The reason
    // is that, with #config_target, the entire contact.settings object gets
    // validated. But values, other than `user_default_enabled` might not pass
    // the validation, and there's nothing the user can do, as such values are
    // not exposed by this form alter. A typical case can be observed when
    // installing the Contact module. The default configuration shipped with the
    // module sets contact.settings:default_form value to 'feedback' but the
    // module doesn't ship any contact form config entity with this ID, hence
    // the config validation will fail.
    $form['#submit'][] = self::class . ':userAdminSettingsSubmit';
  }

  /**
   * Provides an additional submit handler for 'user_admin_settings' form.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   */
  public function userAdminSettingsSubmit(array $form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('contact.settings')
      ->set('user_default_enabled', $form_state->getValue('contact_default_status'))
      ->save();
  }

}
