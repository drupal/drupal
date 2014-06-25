<?php

/**
 * @file
 * Definition of Drupal\Core\Mail\MailInterface.
 */

namespace Drupal\Core\Mail;

/**
 * Defines an interface for pluggable mail back-ends.
 *
 * @see \Drupal\Core\Annotation\Mail
 * @see \Drupal\Core\Mail\MailManager
 * @see plugin_api
 */
interface MailInterface {

  /**
   * Formats a message composed by drupal_mail() prior sending.
   *
   * Allows to preprocess, format, and postprocess a mail message before it is
   * passed to the sending system. By default, all messages may contain HTML and
   * are converted to plain-text by the Drupal\Core\Mail\Plugin\Mail\PhpMail
   * implementation. For example, an alternative implementation could override
   * the default implementation and also sanitize the HTML for usage in a MIME-
   * encoded email, but still invoking the Drupal\Core\Mail\Plugin\Mail\PhpMail
   * implementation to generate an alternate plain-text version for sending.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
   public function format(array $message);

  /**
   * Sends a message composed by drupal_mail().
   *
   * @param array $message
   *   Message array with at least the following elements:
   *   - id: A unique identifier of the email type. Examples: 'contact_user_copy',
   *     'user_password_reset'.
   *   - to: The mail address or addresses where the message will be sent to.
   *     The formatting of this string will be validated with the
   *     @link http://php.net/manual/filter.filters.validate.php PHP email validation filter. @endlink
   *     Some examples:
   *     - user@example.com
   *     - user@example.com, anotheruser@example.com
   *     - User <user@example.com>
   *     - User <user@example.com>, Another User <anotheruser@example.com>
   *   - subject: Subject of the email to be sent. This must not contain any
   *     newline characters, or the mail may not be sent properly.
   *   - body: Message to be sent. Accepts both CRLF and LF line-endings.
   *     Email bodies must be wrapped. You can use drupal_wrap_mail() for
   *     smart plain text wrapping.
   *   - headers: Associative array containing all additional mail headers not
   *     defined by one of the other parameters.  PHP's mail() looks for Cc and
   *     Bcc headers and sends the mail to addresses in these headers too.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted for delivery, otherwise FALSE.
   */
   public function mail(array $message);
}
