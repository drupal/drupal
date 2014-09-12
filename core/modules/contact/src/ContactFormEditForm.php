<?php

/**
 * @file
 * Contains \Drupal\contact\ContactFormEditForm.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for contact form edit forms.
 */
class ContactFormEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $contact_form = $this->entity;
    $default_form = $this->config('contact.settings')->get('default_form');

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $contact_form->label(),
      '#description' => $this->t("Example: 'website feedback' or 'product information'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $contact_form->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'exists' => '\Drupal\contact\Entity\ContactForm::load',
      ),
      '#disabled' => !$contact_form->isNew(),
    );
    $form['recipients'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Recipients'),
      '#default_value' => implode(', ', $contact_form->getRecipients()),
      '#description' => $this->t("Example: 'webmaster@example.com' or 'sales@example.com,support@example.com' . To specify multiple recipients, separate each email address with a comma."),
      '#required' => TRUE,
    );
    $form['reply'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Auto-reply'),
      '#default_value' => $contact_form->getReply(),
      '#description' => $this->t('Optional auto-reply. Leave empty if you do not want to send the user an auto-reply message.'),
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $contact_form->getWeight(),
      '#description' => $this->t('When listing forms, those with lighter (smaller) weights get listed before forms with heavier (larger) weights. Forms with equal weights are sorted alphabetically.'),
    );
    $form['selected'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Make this the default form'),
      '#default_value' => $default_form === $contact_form->id(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // Validate and each email recipient.
    $recipients = explode(',', $form_state->getValue('recipients'));

    foreach ($recipients as &$recipient) {
      $recipient = trim($recipient);
      if (!valid_email_address($recipient)) {
        $form_state->setErrorByName('recipients', $this->t('%recipient is an invalid email address.', array('%recipient' => $recipient)));
      }
    }
    $form_state->setValue('recipients', $recipients);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $contact_form = $this->entity;
    $status = $contact_form->save();
    $contact_settings = $this->config('contact.settings');

    $edit_link = \Drupal::linkGenerator()->generateFromUrl($this->t('Edit'), $this->entity->urlInfo());

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Contact form %label has been updated.', array('%label' => $contact_form->label())));
      $this->logger('contact')->notice('Contact form %label has been updated.', array('%label' => $contact_form->label(), 'link' => $edit_link));
    }
    else {
      drupal_set_message($this->t('Contact form %label has been added.', array('%label' => $contact_form->label())));
      $this->logger('contact')->notice('Contact form %label has been added.', array('%label' => $contact_form->label(), 'link' => $edit_link));
    }

    // Update the default form.
    if ($form_state->getValue('selected')) {
      $contact_settings
        ->set('default_form', $contact_form->id())
        ->save();
    }
    // If it was the default form, empty out the setting.
    elseif ($contact_settings->get('default_form') == $contact_form->id()) {
      $contact_settings
        ->set('default_form', NULL)
        ->save();
    }

    $form_state->setRedirect('contact.form_list');
  }

}
