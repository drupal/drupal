<?php
/**
 * @file
 * MailSystemInterface for logging mails to the filesystem.
 *
 * To enable, save a variable in settings.php (or otherwise) whose value
 * can be as simple as:
 *
 * $conf['mail_system'] = array(
 *   'default-system' => 'DevelMailLog',
 *);
 *
 * Saves to temporary://devel-mails dir by default. Can be changed using
 * 'debug_mail_directory' config setting. Filename pattern controlled by
 * 'debug_mail_file_format' config setting.
 *
 */

namespace Drupal\devel;

use Drupal\Core\Mail\PhpMail;
use Exception;

class DevelMailLog extends PhpMail {

  public function composeMessage($message) {
    $mimeheaders = array();
    $message['headers']['To'] = $message['to'];
    foreach ($message['headers'] as $name => $value) {
      $mimeheaders[] = $name . ': ' . mime_header_encode($value);
    }

    $line_endings = variable_get('mail_line_endings', MAIL_LINE_ENDINGS);
    $output = join($line_endings, $mimeheaders) . $line_endings;
    $output .= $message['subject'] . $line_endings;
    $output .= preg_replace('@\r?\n@', $line_endings, $message['body']);
    return $output;
  }

  public function getFileName($message) {
    $output_directory = $this->getOutputDirectory();
    $this->makeOutputDirectory($output_directory);
    $output_file_format = config('devel.settings')->get('debug_mail_file_format');

    $tokens = array(
      '%to' => $message['to'],
      '%subject' => $message['subject'],
      '%datetime' => date('y-m-d_his'),
    );
    return $output_directory . '/' . $this->dirify(str_replace(array_keys($tokens), array_values($tokens), $output_file_format));
  }

  private function dirify($string) {
    return preg_replace('/[^a-zA-Z0-9_\-\.@]/', '_', $string);
  }
  /**
   * Save an e-mail message to a file, using Drupal variables and default settings.
   *
   * @see http://php.net/manual/en/function.mail.php
   * @see drupal_mail()
   *
   * @param $message
   *   A message array, as described in hook_mail_alter().
   * @return
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  public function mail(array $message) {
    $output = $this->composeMessage($message);
    $output_file = $this->getFileName($message);

    return file_put_contents($output_file, $output);
  }

  protected function makeOutputDirectory($output_directory) {
    if (!file_prepare_directory($output_directory, FILE_CREATE_DIRECTORY)) {
      throw new Exception("Unable to continue sending mail, $output_directory is not writable");
    }
  }

  public function getOutputDirectory() {
    return config('devel.settings')->get('debug_mail_directory');
  }
}
