<?php

/**
 * @file
 * API functions for processing and sending email.
 */

use Drupal\Core\Mail\MailFormatHelper;

/**
 * Composes and optionally sends an email message.
 *
 * Sending an email works with defining an email template (subject, text
 * and possibly email headers) and the replacement values to use in the
 * appropriate places in the template. Processed email templates are
 * requested from hook_mail() from the module sending the email. Any module
 * can modify the composed email message array using hook_mail_alter().
 * Finally drupal_mail_system()->mail() sends the email, which can
 * be reused if the exact same composed email is to be sent to multiple
 * recipients.
 *
 * Finding out what language to send the email with needs some consideration.
 * If you send email to a user, her preferred language should be fine, so
 * use user_preferred_langcode(). If you send email based on form values
 * filled on the page, there are two additional choices if you are not
 * sending the email to a user on the site. You can either use the language
 * used to generate the page or the site default language. See
 * language_default(). The former is good if sending email to the person
 * filling the form, the later is good if you send email to an address
 * previously set up (like contact addresses in a contact form).
 *
 * Taking care of always using the proper language is even more important
 * when sending emails in a row to multiple users. Hook_mail() abstracts
 * whether the mail text comes from an administrator setting or is
 * static in the source code. It should also deal with common mail tokens,
 * only receiving $params which are unique to the actual email at hand.
 *
 * An example:
 *
 * @code
 *   function example_notify($accounts) {
 *     foreach ($accounts as $account) {
 *       $params['account'] = $account;
 *       // example_mail() will be called based on the first drupal_mail() parameter.
 *       drupal_mail('example', 'notice', $account->mail, user_preferred_langcode($account), $params);
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
 * Another example, which uses drupal_mail() to format a message for sending
 * later:
 *
 * @code
 *   $params = array('current_conditions' => $data);
 *   $to = 'user@example.com';
 *   $message = drupal_mail('example', 'notice', $to, $langcode, $params, FALSE);
 *   // Only add to the spool if sending was not canceled.
 *   if ($message['send']) {
 *     example_spool_message($message);
 *   }
 * @endcode
 *
 * @param string $module
 *   A module name to invoke hook_mail() on. The {$module}_mail() hook will be
 *   called to complete the $message structure which will already contain common
 *   defaults.
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
 *   If TRUE, drupal_mail() will call drupal_mail_system()->mail() to deliver
 *   the message, and store the result in $message['result']. Modules
 *   implementing hook_mail_alter() may cancel sending by setting
 *   $message['send'] to FALSE.
 *
 * @return array
 *   The $message array structure containing all details of the
 *   message. If already sent ($send = TRUE), then the 'result' element
 *   will contain the success indicator of the email, failure being already
 *   written to the watchdog. (Success means nothing more than the message being
 *   accepted at php-level, which still doesn't guarantee it to be delivered.)
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('plugin.manager.mail')->mail() in procedural code. In
 *   Object-Oriented code inject the 'plugin.manager.mail' service and use the
 *   ::mail() method.
 */
function drupal_mail($module, $key, $to, $langcode, $params = array(), $reply = NULL, $send = TRUE) {
  return \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode, $params, $reply, $send);
}

/**
 * Returns an instance of the mail plugin to use for a given message ID.
 *
 * @param string $module
 *   The module name which was used by drupal_mail() to invoke hook_mail().
 * @param string $key
 *   A key to identify the email sent. The final message ID for the email
 *   alter hook in drupal_mail() would have been {$module}_{$key}.
 *
 * @return \Drupal\Core\Mail\MailInterface
 *   A mail plugin instance.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 *
 * @see \Drupal\Core\Mail\MailManager::getInstance()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('plugin.manager.mail')->getInstance() in procedural
 *   code. In Object-Oriented code inject the 'plugin.manager.mail' service and
 *   use the ::getInstance() method.
 */
function drupal_mail_system($module, $key) {
  return \Drupal::service('plugin.manager.mail')->getInstance(array('module' => $module, 'key' => $key));
}

/**
 * Performs format=flowed soft wrapping for mail (RFC 3676).
 *
 * We use delsp=yes wrapping, but only break non-spaced languages when
 * absolutely necessary to avoid compatibility issues.
 *
 * We deliberately use LF rather than CRLF, see drupal_mail().
 *
 * @param $text
 *   The plain text to process.
 * @param $indent (optional)
 *   A string to indent the text with. Only '>' characters are repeated on
 *   subsequent wrapped lines. Others are replaced by spaces.
 *
 * @return
 *   The content of the email as a string with formatting applied.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Core\Utility\Mail::wrapMail().
 */
function drupal_wrap_mail($text, $indent = '') {
  return MailFormatHelper::wrapMail($text, $indent);
}

/**
 * Transforms an HTML string into plain text, preserving its structure.
 *
 * The output will be suitable for use as 'format=flowed; delsp=yes' text
 * (RFC 3676) and can be passed directly to drupal_mail() for sending.
 *
 * We deliberately use LF rather than CRLF, see drupal_mail().
 *
 * This function provides suitable alternatives for the following tags:
 * <a> <em> <i> <strong> <b> <br> <p> <blockquote> <ul> <ol> <li> <dl> <dt>
 * <dd> <h1> <h2> <h3> <h4> <h5> <h6> <hr>
 *
 * @param $string
 *   The string to be transformed.
 * @param $allowed_tags (optional)
 *   If supplied, a list of tags that will be transformed. If omitted, all
 *   all supported tags are transformed.
 *
 * @return
 *   The transformed string.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Core\Utility\Mail::htmlToText().
 */
function drupal_html_to_text($string, $allowed_tags = NULL) {
  return MailFormatHelper::htmlToText($string, $allowed_tags);
}
