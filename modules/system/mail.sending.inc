<?php
// $Id$

/**
 * @file
 * Drupal core implementations of the DrupalMailSendingInterface.
 */

/**
 * The default Drupal mail sending library using PHP's mail function.
 */
class DefaultMailSystem implements MailSystemInterface {
  /**
   * Send an e-mail message, using Drupal variables and default settings.
   * @see http://php.net/manual/en/function.mail.php the PHP function reference
   *   for mail().
   * @see drupal_mail() for information on how $message is composed.
   *
   * @param $message
   *   Message array as described by DrupalMailSendingInterface.
   * @return
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  public function mail(array $message) {
    $mimeheaders = array();
    foreach ($message['headers'] as $name => $value) {
      $mimeheaders[] = $name . ': ' . mime_header_encode($value);
    }
    return mail(
      $message['to'],
      mime_header_encode($message['subject']),
      // Note: e-mail uses CRLF for line-endings, but PHP's API requires LF.
      // They will appear correctly in the actual e-mail that is sent.
      str_replace("\r", '', $message['body']),
      // For headers, PHP's API suggests that we use CRLF normally,
      // but some MTAs incorrectly replace LF with CRLF. See #234403.
      join("\n", $mimeheaders)
    );
  }
}

/**
 * A mail sending implementation that captures sent messages to a variable.
 *
 * This class is for running tests or for development.
 */
class TestingMailSystem implements MailSystemInterface {

  /**
   * Accept an e-mail message and store it in a variable.
   *
   * @param $message
   *   An e-mail message.
   */
  public function mail(array $message) {
    $captured_emails = variable_get('drupal_test_email_collector', array());
    $captured_emails[] = $message;
    variable_set('drupal_test_email_collector', $captured_emails);
    return TRUE;
  }
}

