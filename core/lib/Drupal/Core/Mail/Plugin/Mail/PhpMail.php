<?php

namespace Drupal\Core\Mail\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\UnstructuredHeader;

// cspell:ignore windir

/**
 * Defines the default Drupal mail backend, using PHP's native mail() function.
 *
 * @Mail(
 *   id = "php_mail",
 *   label = @Translation("Default PHP mailer"),
 *   description = @Translation("Sends the message as plain text, using PHP's native mail() function.")
 * )
 */
class PhpMail implements MailInterface {

  /**
   * A list of headers that can contain multiple email addresses.
   *
   * @see \Symfony\Component\Mime\Header\Headers::HEADER_CLASS_MAP
   */
  private const MAILBOX_LIST_HEADERS = ['from', 'to', 'reply-to', 'cc', 'bcc'];

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * PhpMail constructor.
   */
  public function __construct() {
    $this->configFactory = \Drupal::configFactory();
    $this->request = \Drupal::request();
  }

  /**
   * Concatenates and wraps the email body for plain-text mails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    // Convert any HTML to plain-text.
    $message['body'] = MailFormatHelper::htmlToText($message['body']);
    // Wrap the mail body for sending.
    $message['body'] = MailFormatHelper::wrapMail($message['body']);

    return $message;
  }

  /**
   * Sends an email message.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   *
   * @see http://php.net/manual/function.mail.php
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function mail(array $message) {
    // If 'Return-Path' isn't already set in php.ini, we pass it separately
    // as an additional parameter instead of in the header.
    if (isset($message['headers']['Return-Path'])) {
      $return_path_set = strpos(ini_get('sendmail_path'), ' -f');
      if (!$return_path_set) {
        $message['Return-Path'] = $message['headers']['Return-Path'];
        unset($message['headers']['Return-Path']);
      }
    }

    $headers = new Headers();
    foreach ($message['headers'] as $name => $value) {
      if (in_array(strtolower($name), self::MAILBOX_LIST_HEADERS, TRUE)) {
        // Split values by comma, but ignore commas encapsulated in double
        // quotes.
        $value = str_getcsv($value, ',');
      }
      $headers->addHeader($name, $value);
    }
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);
    // Prepare mail commands.
    $mail_subject = (new UnstructuredHeader('subject', $message['subject']))->getBodyAsString();
    // Note: email uses CRLF for line-endings. PHP's API requires LF
    // on Unix and CRLF on Windows. Drupal automatically guesses the
    // line-ending format appropriate for your system. If you need to
    // override this, adjust $settings['mail_line_endings'] in settings.php.
    $mail_body = preg_replace('@\r?\n@', $line_endings, $message['body']);
    $mail_headers = $headers->toString();

    if (!$this->request->server->has('WINDIR') && !str_contains($this->request->server->get('SERVER_SOFTWARE'), 'Win32')) {
      // On most non-Windows systems, the "-f" option to the sendmail command
      // is used to set the Return-Path. There is no space between -f and
      // the value of the return path.
      // We validate the return path, unless it is equal to the site mail, which
      // we assume to be safe.
      $site_mail = $this->configFactory->get('system.site')->get('mail');
      $additional_params = isset($message['Return-Path']) && ($site_mail === $message['Return-Path'] || static::_isShellSafe($message['Return-Path'])) ? '-f' . $message['Return-Path'] : '';
      $mail_result = $this->doMail(
        $message['to'],
        $mail_subject,
        $mail_body,
        $mail_headers,
        $additional_params
      );
    }
    else {
      // On Windows, PHP will use the value of sendmail_from for the
      // Return-Path header.
      $old_from = ini_get('sendmail_from');
      ini_set('sendmail_from', $message['Return-Path']);
      $mail_result = $this->doMail(
        $message['to'],
        $mail_subject,
        $mail_body,
        $mail_headers
      );
      ini_set('sendmail_from', $old_from);
    }

    return $mail_result;
  }

  /**
   * Wrapper around PHP's mail() function.
   *
   * We suppress warnings and notices from mail() because of issues on some
   * hosts. The return value of this method will still indicate whether mail was
   * sent successfully.
   *
   * @param string $to
   *   Receiver, or receivers of the mail.
   * @param string $subject
   *   Subject of the email to be sent.
   * @param string $message
   *   Message to be sent.
   * @param array|string $additional_headers
   *   (optional) String or array to be inserted at the end of the email header.
   * @param string $additional_params
   *   (optional) Can be used to pass additional flags as command line options.
   *
   * @see mail()
   */
  protected function doMail(string $to, string $subject, string $message, array|string $additional_headers = [], string $additional_params = ''): bool {
    return @mail(
      $to,
      $subject,
      $message,
      $additional_headers,
      $additional_params
    );
  }

  /**
   * Disallows potentially unsafe shell characters.
   *
   * Functionally similar to PHPMailer::isShellSafe() which resulted from
   * CVE-2016-10045. Note that escapeshellarg and escapeshellcmd are inadequate
   * for this purpose.
   *
   * @param string $string
   *   The string to be validated.
   *
   * @return bool
   *   True if the string is shell-safe.
   *
   * @see https://github.com/PHPMailer/PHPMailer/issues/924
   * @see https://github.com/PHPMailer/PHPMailer/blob/v5.2.21/class.phpmailer.php#L1430
   *
   * @todo Rename to ::isShellSafe() and/or discuss whether this is the correct
   *   location for this helper.
   */
  protected static function _isShellSafe($string) {
    if (escapeshellcmd($string) !== $string || !in_array(escapeshellarg($string), ["'$string'", "\"$string\""])) {
      return FALSE;
    }
    if (preg_match('/[^a-zA-Z0-9@_\-.]/', $string) !== 0) {
      return FALSE;
    }
    return TRUE;
  }

}
