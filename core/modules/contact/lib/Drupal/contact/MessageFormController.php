<?php

/**
 * @file
 * Definition of Drupal\contact\MessageFormController.
 */

namespace Drupal\contact;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Language\Language;
use Drupal\user\UserInterface;

/**
 * Form controller for contact message forms.
 */
class MessageFormController extends ContentEntityFormController {

  /**
   * The message being used by this form.
   *
   * @var \Drupal\contact\MessageInterface
   */
  protected $entity;

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $user = \Drupal::currentUser();
    $message = $this->entity;
    $form = parent::form($form, $form_state, $message);
    $form['#attributes']['class'][] = 'contact-form';

    if (!empty($message->preview)) {
      $form['preview'] = array(
        '#theme_wrappers' => array('container__preview'),
        '#attributes' => array('class' => array('preview')),
      );
      $form['preview']['message'] = entity_view($message, 'full');
    }

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Your name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['mail'] = array(
      '#type' => 'email',
      '#title' => t('Your e-mail address'),
      '#required' => TRUE,
    );
    if ($user->isAnonymous()) {
      $form['#attached']['library'][] = 'core/jquery.cookie';
      $form['#attributes']['class'][] = 'user-info-from-cookie';
    }
    // Do not allow authenticated users to alter the name or e-mail values to
    // prevent the impersonation of other users.
    else {
      $form['name']['#type'] = 'item';
      $form['name']['#value'] = $user->getUsername();
      $form['name']['#required'] = FALSE;
      $form['name']['#markup'] = String::checkPlain($user->getUsername());

      $form['mail']['#type'] = 'item';
      $form['mail']['#value'] = $user->getEmail();
      $form['mail']['#required'] = FALSE;
      $form['mail']['#markup'] = String::checkPlain($user->getEmail());
    }

    // The user contact form has a preset recipient.
    if ($message->isPersonal()) {
      $form['recipient'] = array(
        '#type' => 'item',
        '#title' => t('To'),
        '#value' => $message->getPersonalRecipient()->id(),
        'name' => array(
          '#theme' => 'username',
          '#account' => $message->getPersonalRecipient(),
        ),
      );
    }

    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#maxlength' => 100,
      '#required' => TRUE,
    );
    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#required' => TRUE,
      '#rows' => 12,
    );

    $form['copy'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send yourself a copy.'),
      // Do not allow anonymous users to send themselves a copy, because it can
      // be abused to spam people.
      '#access' => $user->isAuthenticated(),
    );
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  public function actions(array $form, array &$form_state) {
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = t('Send message');
    $elements['delete']['#access'] = FALSE;
    $elements['preview'] = array(
      '#value' => t('Preview'),
      '#validate' => array(
        array($this, 'validate'),
      ),
      '#submit' => array(
        array($this, 'submit'),
        array($this, 'preview'),
      ),
    );
    return $elements;
  }

  /**
   * Form submission handler for the 'preview' action.
   */
  public function preview(array $form, array &$form_state) {
    $message = $this->entity;
    $message->preview = TRUE;
    $form_state['rebuild'] = TRUE;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $user = \Drupal::currentUser();

    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $message = $this->entity;

    $sender = clone user_load($user->id());
    if ($user->isAnonymous()) {
      // At this point, $sender contains drupal_anonymous_user(), so we need to
      // take over the submitted form values.
      $sender->name = $message->getSenderName();
      $sender->mail = $message->getSenderMail();
      // Save the anonymous user information to a cookie for reuse.
      user_cookie_save(array('name' => $message->getSenderName(), 'mail' => $message->getSenderMail()));
      // For the e-mail message, clarify that the sender name is not verified; it
      // could potentially clash with a username on this site.
      $sender->name = t('!name (not verified)', array('!name' => $message->getSenderName()));
    }

    // Build e-mail parameters.
    $params['contact_message'] = $message;
    $params['sender'] = $sender;

    if (!$message->isPersonal()) {
      // Send to the category recipient(s), using the site's default language.
      $category = $message->getCategory();
      $params['contact_category'] = $category;

      $to = implode(', ', $category->recipients);
      $recipient_langcode = language_default()->id;
    }
    elseif ($recipient = $message->getPersonalRecipient()) {
      // Send to the user in the user's preferred language.
      $to = $recipient->getEmail();
      $recipient_langcode = $recipient->getPreferredLangcode();
      $params['recipient'] = $recipient;
    }
    else {
      throw new \RuntimeException(t('Unable to determine message recipient.'));
    }

    // Send e-mail to the recipient(s).
    $key_prefix = $message->isPersonal() ? 'user' : 'page';
    drupal_mail('contact', $key_prefix . '_mail', $to, $recipient_langcode, $params, $sender->getEmail());

    // If requested, send a copy to the user, using the current language.
    if ($message->copySender()) {
      drupal_mail('contact', $key_prefix . '_copy', $sender->getEmail(), $language_interface->id, $params, $sender->getEmail());
    }

    // If configured, send an auto-reply, using the current language.
    if (!$message->isPersonal() && $category->reply) {
      // User contact forms do not support an auto-reply message, so this
      // message always originates from the site.
      drupal_mail('contact', 'page_autoreply', $sender->getEmail(), $language_interface->id, $params);
    }

    \Drupal::service('flood')->register('contact', \Drupal::config('contact.settings')->get('flood.interval'));
    if (!$message->isPersonal()) {
      watchdog('contact', '%sender-name (@sender-from) sent an e-mail regarding %category.', array(
        '%sender-name' => $sender->name,
        '@sender-from' => $sender->getEmail(),
        '%category' => $category->label(),
      ));
    }
    else {
      watchdog('contact', '%sender-name (@sender-from) sent %recipient-name an e-mail.', array(
        '%sender-name' => $sender->name,
        '@sender-from' => $sender->getEmail(),
        '%recipient-name' => $message->recipient->name,
      ));
    }

    drupal_set_message(t('Your message has been sent.'));

    // To avoid false error messages caused by flood control, redirect away from
    // the contact form; either to the contacted user account or the front page.
    if ($message->isPersonal() && user_access('access user profiles')) {
      $form_state['redirect_route'] = $message->getPersonalRecipient()->urlInfo();
    }
    else {
      $form_state['redirect_route']['route_name'] = '<front>';
    }
  }
}
