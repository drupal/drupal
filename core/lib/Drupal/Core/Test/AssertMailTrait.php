<?php

namespace Drupal\Core\Test;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides methods for testing emails sent during test runs.
 */
trait AssertMailTrait {

  /**
   * Gets an array containing all emails sent during this test case.
   *
   * @param array $filter
   *   An array containing key/value pairs used to filter the emails that are
   *   returned.
   *
   * @return array
   *   An array containing email messages captured during the current test.
   */
  protected function getMails(array $filter = []) {
    $captured_emails = $this->container->get('state')->get('system.test_mail_collector', []);
    $filtered_emails = [];

    foreach ($captured_emails as $message) {
      foreach ($filter as $key => $value) {
        if (!isset($message[$key]) || $message[$key] != $value) {
          continue 2;
        }
      }
      $filtered_emails[] = $message;
    }

    return $filtered_emails;
  }

  /**
   * Asserts that the most recently sent email message has the given value.
   *
   * The field in $name must have the content described in $value.
   *
   * @param string $name
   *   Name of field or message property to assert. Examples: subject, body,
   *   id, ...
   * @param string $value
   *   Value of the field to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t(). Use double quotes and embed variables directly in
   *   message text, or use sprintf() if necessary. Avoid the use of
   *   \Drupal\Component\Render\FormattableMarkup unless you cast the object to
   *   a string. If left blank, a default message will be displayed.
   *
   * @return bool
   *   TRUE on pass.
   */
  protected function assertMail($name, $value = '', $message = '') {
    $captured_emails = $this->container->get('state')->get('system.test_mail_collector') ?: [];
    $email = end($captured_emails);
    $this->assertIsArray($email, $message);
    $this->assertArrayHasKey($name, $email, $message);
    $this->assertEquals($value, $email[$name], $message);
    return TRUE;
  }

  /**
   * Asserts that the most recently sent email message has the string in it.
   *
   * @param string $field_name
   *   Name of field or message property to assert: subject, body, id, ...
   * @param string $string
   *   String to search for.
   * @param int $email_depth
   *   Number of emails to search for string, starting with most recent.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t(). Use double quotes and embed variables directly in
   *   message text, or use sprintf() if necessary. Avoid the use of
   *   \Drupal\Component\Render\FormattableMarkup unless you cast the object to
   *   a string. If left blank, a default message will be displayed.
   */
  protected function assertMailString($field_name, $string, $email_depth, $message = '') {
    $mails = $this->getMails();
    $string_found = FALSE;
    // Cast MarkupInterface objects to string.
    $string = (string) $string;
    for ($i = count($mails) - 1; $i >= count($mails) - $email_depth && $i >= 0; $i--) {
      $mail = $mails[$i];
      // Normalize whitespace, as we don't know what the mail system might have
      // done. Any run of whitespace becomes a single space.
      $normalized_mail = preg_replace('/\s+/', ' ', $mail[$field_name]);
      $normalized_string = preg_replace('/\s+/', ' ', $string);
      $string_found = str_contains($normalized_mail, $normalized_string);
      if ($string_found) {
        break;
      }
    }
    if (!$message) {
      $message = new FormattableMarkup('Expected text found in @field of email message: "@expected".', ['@field' => $field_name, '@expected' => $string]);
    }
    $this->assertTrue($string_found, $message);
  }

  /**
   * Asserts that the most recently sent email message has the pattern in it.
   *
   * @param string $field_name
   *   Name of field or message property to assert: subject, body, id, ...
   * @param string $regex
   *   Pattern to search for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t(). Use double quotes and embed variables directly in
   *   message text, or use sprintf() if necessary. Avoid the use of
   *   \Drupal\Component\Render\FormattableMarkup unless you cast the object to
   *   a string. If left blank, a default message will be displayed.
   */
  protected function assertMailPattern($field_name, $regex, $message = '') {
    $mails = $this->getMails();
    $mail = end($mails);
    $regex_found = preg_match("/$regex/", $mail[$field_name]);
    if (!$message) {
      $message = new FormattableMarkup('Expected text found in @field of email message: "@expected".', ['@field' => $field_name, '@expected' => $regex]);
    }
    $this->assertTrue((bool) $regex_found, $message);
  }

}
