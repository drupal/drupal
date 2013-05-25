<?php

/**
 * @file
 * Definition of Drupal\contact\MessageFormController.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Language\Language;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Form controller for contact message forms.
 */
class MessageFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    global $user;
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
    if (!$user->uid) {
      $form['#attached']['library'][] = array('system', 'jquery.cookie');
      $form['#attributes']['class'][] = 'user-info-from-cookie';
    }
    // Do not allow authenticated users to alter the name or e-mail values to
    // prevent the impersonation of other users.
    else {
      $form['name']['#type'] = 'item';
      $form['name']['#value'] = $user->name;
      $form['name']['#required'] = FALSE;
      $form['name']['#markup'] = check_plain(user_format_name($user));

      $form['mail']['#type'] = 'item';
      $form['mail']['#value'] = $user->mail;
      $form['mail']['#required'] = FALSE;
      $form['mail']['#markup'] = check_plain($user->mail);
    }

    // The user contact form only has a recipient, not a category.
    // @todo Convert user contact form into a locked contact category.
    if ($message->recipient instanceof User) {
      $form['recipient'] = array(
        '#type' => 'item',
        '#title' => t('To'),
        '#value' => $message->recipient,
        'name' => array(
          '#theme' => 'username',
          '#account' => $message->recipient,
        ),
      );
    }
    else {
      $form['category'] = array(
        '#type' => 'value',
        '#value' => $message->category,
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
      '#access' => !empty($user->uid),
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
    global $user;

    $language_interface = language(Language::TYPE_INTERFACE);
    $message = $this->entity;

    $sender = clone user_load($user->uid);
    if (!$user->uid) {
      // At this point, $sender contains drupal_anonymous_user(), so we need to
      // take over the submitted form values.
      $sender->name = $message->name;
      $sender->mail = $message->mail;
      // Save the anonymous user information to a cookie for reuse.
      user_cookie_save(array('name' => $message->name, 'mail' => $message->mail));
      // For the e-mail message, clarify that the sender name is not verified; it
      // could potentially clash with a username on this site.
      $sender->name = t('!name (not verified)', array('!name' => $message->name));
    }

    // Build e-mail parameters.
    $params['contact_message'] = $message;
    $params['sender'] = $sender;

    if ($message->category) {
      // Send to the category recipient(s), using the site's default language.
      $category = entity_load('contact_category', $message->category);
      $params['contact_category'] = $category;

      $to = implode(', ', $category->recipients);
      $recipient_langcode = language_default()->langcode;
    }
    elseif ($message->recipient instanceof User) {
      // Send to the user in the user's preferred language.
      $to = $message->recipient->mail;
      $recipient_langcode = user_preferred_langcode($message->recipient);
    }
    else {
      throw new \RuntimeException(t('Unable to determine message recipient.'));
    }

    // Send e-mail to the recipient(s).
    drupal_mail('contact', 'page_mail', $to, $recipient_langcode, $params, $sender->mail);

    // If requested, send a copy to the user, using the current language.
    if ($message->copy) {
      drupal_mail('contact', 'page_copy', $sender->mail, $language_interface->langcode, $params, $sender->mail);
    }

    // If configured, send an auto-reply, using the current language.
    if ($message->category && $category->reply) {
      // User contact forms do not support an auto-reply message, so this
      // message always originates from the site.
      drupal_mail('contact', 'page_autoreply', $sender->mail, $language_interface->langcode, $params);
    }

    \Drupal::service('flood')->register('contact', config('contact.settings')->get('flood.interval'));
    if ($message->category) {
      watchdog('contact', '%sender-name (@sender-from) sent an e-mail regarding %category.', array(
        '%sender-name' => $sender->name,
        '@sender-from' => $sender->mail,
        '%category' => $category->label(),
      ));
    }
    else {
      watchdog('contact', '%sender-name (@sender-from) sent %recipient-name an e-mail.', array(
        '%sender-name' => $sender->name,
        '@sender-from' => $sender->mail,
        '%recipient-name' => $message->recipient->name,
      ));
    }

    drupal_set_message(t('Your message has been sent.'));

    // To avoid false error messages caused by flood control, redirect away from
    // the contact form; either to the contacted user account or the front page.
    if ($message->recipient instanceof User && user_access('access user profiles')) {
      $uri = $message->recipient->uri();
      $form_state['redirect'] = array($uri['path'], $uri['options']);
    }
    else {
      $form_state['redirect'] = '';
    }
  }
}
