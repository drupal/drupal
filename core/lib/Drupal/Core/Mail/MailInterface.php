<?php

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
   * Formats a message prior to sending.
   *
   * Allows to preprocess, format, and postprocess a mail message before it is
   * passed to the sending system. The message body is received as an array of
   * lines that are either strings or objects implementing
   * \Drupal\Component\Render\MarkupInterface. It must be converted to the
   * format expected by mail() which is a single string that can be either
   * plain text or HTML. In the HTML case an alternate plain-text version can
   * be returned in $message['plain'].
   *
   * The conversion process consists of the following steps:
   * - If the output is HTML then convert any input line that is a string using
   *   \Drupal\Component\Utility\Html\Html::Escape().
   * - If the output is plain text then convert any input line that is markup
   *   using \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   * - Join the input lines into a single string.
   * - Wrap long lines using \Drupal\Core\Mail\MailFormatHelper::wrapMail().
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   *
   * @see \Drupal\Core\Mail\MailManagerInterface
   */
  public function format(array $message);

  /**
   * Sends a message composed by \Drupal\Core\Mail\MailManagerInterface->mail().
   *
   * @param array $message
   *   Message array with at least the following elements:
   *   - id: A unique identifier of the email type. Examples:
   *     'contact_user_copy', 'user_password_reset'.
   *   - to: The mail address or addresses where the message will be sent to.
   *     The formatting of this string will be validated with the
   *     @link http://php.net/manual/filter.filters.validate.php PHP email validation filter. @endlink
   *     Some examples:
   *     - user@example.com
   *     - user@example.com, anotheruser@example.com
   *     - User <user@example.com>
   *     - User <user@example.com>, Another User <anotheruser@example.com>
   *   - subject: Subject of the email to be sent. This must not contain any
   *     newline characters, or the mail may not be sent properly. The subject
   *     is converted to plain text by the mail plugin manager.
   *   - body: Message to be sent. Accepts both CRLF and LF line-endings.
   *     Email bodies must be wrapped. For smart plain text wrapping you can use
   *     \Drupal\Core\Mail\MailFormatHelper::wrapMail() .
   *   - headers: Associative array containing all additional mail headers not
   *     defined by one of the other parameters.  PHP's mail() looks for Cc and
   *     Bcc headers and sends the mail to addresses in these headers too.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted for delivery, otherwise FALSE.
   */
  public function mail(array $message);

}
