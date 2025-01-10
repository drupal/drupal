<?php

namespace Drupal\standard\Hook;

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for standard.
 */
class StandardHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for install_configure_form().
   *
   * Allows the profile to alter the site configuration form.
   */
  #[Hook('form_install_configure_form_alter')]
  public function formInstallConfigureFormAlter(&$form, FormStateInterface $form_state): void {
    $form['#submit'][] = [$this, 'installConfigureSubmit'];
  }

  /**
   * Submission handler to sync the contact.form.feedback recipient.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function installConfigureSubmit(array $form, FormStateInterface $form_state): void {
    $site_mail = $form_state->getValue('site_mail');
    ContactForm::load('feedback')->setRecipients([$site_mail])->trustData()->save();
  }

}
