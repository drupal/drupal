<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Mail\MailFormatHelperTest.
 */

namespace Drupal\Tests\Core\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Mail\MailFormatHelper
 * @group Mail
 */
class MailFormatHelperTest extends UnitTestCase {

  /**
   * @covers ::wrapMail
   */
  public function testWrapMail() {
    $delimiter = "End of header\n";
    $long_file_name = $this->randomMachineName(64) . '.docx';
    $headers_in_body = 'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document; name="' . $long_file_name . "\"\n";
    $headers_in_body .= "Content-Transfer-Encoding: base64\n";
    $headers_in_body .= 'Content-Disposition: attachment; filename="' . $long_file_name . "\"\n";
    $headers_in_body .= 'Content-Description: ' . $this->randomMachineName(64);
    $body = $this->randomMachineName(76) . ' ' . $this->randomMachineName(6);
    $wrapped_text = MailFormatHelper::wrapMail($headers_in_body . $delimiter . $body);
    list($processed_headers, $processed_body) = explode($delimiter, $wrapped_text);

    // Check that the body headers were not wrapped even though some exceeded
    // 77 characters.
    $this->assertEquals($headers_in_body, $processed_headers, 'Headers in the body are not wrapped.');
    // Check that the body text is wrapped.
    $this->assertEquals(wordwrap($body, 77, " \n"), $processed_body, 'Body text is wrapped.');
  }

}
