<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Mail\WrapMailUnitTest.
 */

namespace Drupal\system\Tests\Mail;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests the functionality of drupal_wrap_mail().
 */
class WrapMailUnitTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name'  => 'Mail wrapping',
      'description' => 'Tests drupal_wrap_mail().',
      'group' => 'Mail',
    );
  }

  /**
   * Makes sure that drupal_wrap_mail() wraps the correct types of lines.
   */
  function testDrupalWrapMail() {
    $delimiter = "End of header\n";
    $long_file_name = $this->randomName(64) . '.docx';
    $headers_in_body = 'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document; name="' . $long_file_name . "\"\n";
    $headers_in_body .= "Content-Transfer-Encoding: base64\n";
    $headers_in_body .= 'Content-Disposition: attachment; filename="' . $long_file_name . "\"\n";
    $headers_in_body .= 'Content-Description: ' . $this->randomName(64);
    $body = $this->randomName(76) . ' ' . $this->randomName(6);
    $wrapped_text = drupal_wrap_mail($headers_in_body . $delimiter . $body);
    list($processed_headers, $processed_body) = explode($delimiter, $wrapped_text);

    // Check that the body headers were not wrapped even though some exceeded
    // 77 characters.
    $this->assertEqual($headers_in_body, $processed_headers, 'Headers in the body are not wrapped.');
    // Check that the body text is wrapped.
    $this->assertEqual(wordwrap($body, 77, " \n"), $processed_body, 'Body text is wrapped.');
  }
}

