<?php

/**
 * @file
 * Contains \Drupal\Core\Mail\MailManagerInterface.
 */

namespace Drupal\Core\Mail;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for sending mail.
 */
interface MailManagerInterface extends PluginManagerInterface {

  /**
   * Composes and optionally sends an email message.
   *
   * Sending an email works with defining an email template (subject, text and
   * possibly email headers) and the replacement values to use in the
   * appropriate places in the template. Processed email templates are requested
   * from hook_mail() from the module sending the email. Any module can modify
   * the composed email message array using hook_mail_alter(). Finally
   * \Drupal::service('plugin.manager.mail')->mail() sends the email, which can
   * be reused if the exact same composed email is to be sent to multiple
   * recipients.
   *
   * Finding out what language to send the email with needs some consideration.
   * If you send email to a user, her preferred language should be fine, so use
   * user_preferred_langcode(). If you send email based on form values filled on
   * the page, there are two additional choices if you are not sending the email
   * to a user on the site. You can either use the language used to generate the
   * page or the site default language. See
   * Drupal\Core\Language\LanguageManagerInterface::getDefaultLanguage(). The
   * former is good if sending email to the person filling the form, the later
   * is good if you send email to an address previously set up (like contact
   * addresses in a contact form).
   *
   * Taking care of always using the proper language is even more important when
   * sending emails in a row to multiple users. Hook_mail() abstracts whether
   * the mail text comes from an administrator setting or is static in the
   * source code. It should also deal with common mail tokens, only receiving
   * $params which are unique to the actual email at hand.
   *
   * An example:
   *
   * @code
   *   function example_notify($accounts) {
   *     foreach ($accounts as $account) {
   *       $params['account'] = $account;
   *       // example_mail() will be called based on the first
   *       // MailManagerInterface->mail() parameter.
   *       \Drupal::service('plugin.manager.mail')->mail('example', 'notice', $account->mail, user_preferred_langcode($account), $params);
   *     }
   *   }
   *
   *   function example_mail($key, &$message, $params) {
   *     $data['user'] = $params['account'];
   *     $options['langcode'] = $message['langcode'];
   *     user_mail_tokens($variables, $data, $options);
   *     switch($key) {
   *       case 'notice':
   *         // If the recipient can receive such notices by instant-message, do
   *         // not send by email.
   *         if (example_im_send($key, $message, $params)) {
   *           $message['send'] = FALSE;
   *           break;
   *         }
   *         $message['subject'] = t('Notification from !site', $variables, $options);
   *         $message['body'][] = t("Dear !username\n\nThere is new content available on the site.", $variables, $options);
   *         break;
   *     }
   *   }
   * @endcode
   *
   * Another example, which uses MailManagerInterface->mail() to format a
   * message for sending later:
   *
   * @code
   *   $params = array('current_conditions' => $data);
   *   $to = 'user@example.com';
   *   $message = \Drupal::service('plugin.manager.mail')->mail('example', 'notice', $to, $langcode, $params, FALSE);
   *   // Only add to the spool if sending was not canceled.
   *   if ($message['send']) {
   *     example_spool_message($message);
   *   }
   * @endcode
   *
   * @param string $module
   *   A module name to invoke hook_mail() on. The {$module}_mail() hook will be
   *   called to complete the $message structure which will already contain
   *   common defaults.
   * @param string $key
   *   A key to identify the email sent. The final message ID for email altering
   *   will be {$module}_{$key}.
   * @param string $to
   *   The email address or addresses where the message will be sent to. The
   *   formatting of this string will be validated with the
   *   @link http://php.net/manual/filter.filters.validate.php PHP email validation filter. @endlink
   *   Some examples are:
   *   - user@example.com
   *   - user@example.com, anotheruser@example.com
   *   - User <user@example.com>
   *   - User <user@example.com>, Another User <anotheruser@example.com>
   * @param string $langcode
   *   Language code to use to compose the email.
   * @param array $params
   *   (optional) Parameters to build the email.
   * @param string|null $reply
   *   Optional email address to be used to answer.
   * @param bool $send
   *   If TRUE, call an implementation of
   *   \Drupal\Core\Mail\MailInterface->mail() to deliver the message, and
   *   store the result in $message['result']. Modules implementing
   *   hook_mail_alter() may cancel sending by setting $message['send'] to
   *   FALSE.
   *
   * @return string
   *   The $message array structure containing all details of the message. If
   *   already sent ($send = TRUE), then the 'result' element will contain the
   *   success indicator of the email, failure being already written to the
   *   watchdog. (Success means nothing more than the message being accepted at
   *   php-level, which still doesn't guarantee it to be delivered.)
   */
  public function mail($module, $key, $to, $langcode, $params = array(), $reply = NULL, $send = TRUE);

}
