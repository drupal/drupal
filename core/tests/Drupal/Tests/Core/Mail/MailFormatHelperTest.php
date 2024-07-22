<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Tests\UnitTestCase;

// cspell:ignore officedocument openxmlformats wordprocessingml

/**
 * @coversDefaultClass \Drupal\Core\Mail\MailFormatHelper
 * @group Mail
 */
class MailFormatHelperTest extends UnitTestCase {

  /**
   * @covers ::wrapMail
   */
  public function testWrapMail(): void {
    $delimiter = "End of header\n";
    $long_file_name = $this->randomMachineName(64) . '.docx';
    $headers_in_body = 'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document; name="' . $long_file_name . "\"\n";
    $headers_in_body .= "Content-Transfer-Encoding: base64\n";
    $headers_in_body .= 'Content-Disposition: attachment; filename="' . $long_file_name . "\"\n";
    $headers_in_body .= 'Content-Description: ' . $this->randomMachineName(64);
    $body = $this->randomMachineName(76) . ' ' . $this->randomMachineName(6);
    $wrapped_text = MailFormatHelper::wrapMail($headers_in_body . $delimiter . $body);
    [$processed_headers, $processed_body] = explode($delimiter, $wrapped_text);

    // Check that the body headers were not wrapped even though some exceeded
    // 77 characters.
    $this->assertEquals($headers_in_body, $processed_headers, 'Headers in the body are not wrapped.');
    // Check that the body text is soft-wrapped according to the
    // "format=flowed; delsp=yes" encoding. When interpreting this encoding,
    // mail readers will delete a space at the end of the line; therefore an
    // extra trailing space should be present in the raw body (see RFC 3676).
    $this->assertEquals(wordwrap($body, 77, "  \n"), $processed_body, 'Body text is soft-wrapped.');
  }

}
