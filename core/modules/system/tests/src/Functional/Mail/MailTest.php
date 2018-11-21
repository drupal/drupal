<?php

namespace Drupal\Tests\system\Functional\Mail;

use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\Plugin\Mail\TestMailCollector;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\system_mail_failure_test\Plugin\Mail\TestPhpMailFailure;

/**
 * Performs tests on the pluggable mailing framework.
 *
 * @group Mail
 */
class MailTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simpletest', 'system_mail_failure_test', 'mail_html_test', 'file', 'image'];

  /**
   * Assert that the pluggable mail system is functional.
   */
  public function testPluggableFramework() {
    // Switch mail backends.
    $this->config('system.mail')->set('interface.default', 'test_php_mail_failure')->save();

    // Get the default MailInterface class instance.
    $mail_backend = \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'default', 'key' => 'default']);

    // Assert whether the default mail backend is an instance of the expected
    // class.
    $this->assertTrue($mail_backend instanceof TestPhpMailFailure, 'Default mail interface can be swapped.');

    // Add a module-specific mail backend.
    $this->config('system.mail')->set('interface.mymodule_testkey', 'test_mail_collector')->save();

    // Get the added MailInterface class instance.
    $mail_backend = \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'mymodule', 'key' => 'testkey']);

    // Assert whether the added mail backend is an instance of the expected
    // class.
    $this->assertTrue($mail_backend instanceof TestMailCollector, 'Additional mail interfaces can be added.');
  }

  /**
   * Test that message sending may be canceled.
   *
   * @see simpletest_mail_alter()
   */
  public function testCancelMessage() {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Use the state system collector mail backend.
    $this->config('system.mail')->set('interface.default', 'test_mail_collector')->save();
    // Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', []);

    // Send a test message that simpletest_mail_alter should cancel.
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'cancel_test', 'cancel@example.com', $language_interface->getId());
    // Retrieve sent message.
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);

    // Assert that the message was not actually sent.
    $this->assertFalse($sent_message, 'Message was canceled.');
  }

  /**
   * Checks the From: and Reply-to: headers.
   */
  public function testFromAndReplyToHeader() {
    $language = \Drupal::languageManager()->getCurrentLanguage();

    // Use the state system collector mail backend.
    $this->config('system.mail')->set('interface.default', 'test_mail_collector')->save();
    // Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', []);
    // Send an email with a reply-to address specified.
    $from_email = 'Drupal <simpletest@example.com>';
    $reply_email = 'someone_else@example.com';
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'from_test', 'from_test@example.com', $language, [], $reply_email);
    // Test that the reply-to email is just the email and not the site name
    // and default sender email.
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    $this->assertEqual($from_email, $sent_message['headers']['From'], 'Message is sent from the site email account.');
    $this->assertEqual($reply_email, $sent_message['headers']['Reply-to'], 'Message reply-to headers are set.');
    $this->assertFalse(isset($sent_message['headers']['Errors-To']), 'Errors-to header must not be set, it is deprecated.');

    // Test that long site names containing characters that need MIME encoding
    // works as expected.
    $this->config('system.site')->set('name', 'Drépal this is a very long test sentence to test what happens with very long site names')->save();
    // Send an email and check that the From-header contains the site name.
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'from_test', 'from_test@example.com', $language);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    $this->assertEquals('=?UTF-8?B?RHLDqXBhbCB0aGlzIGlzIGEgdmVyeSBsb25nIHRlc3Qgc2VudGVuY2UgdG8gdGU=?= <simpletest@example.com>', $sent_message['headers']['From'], 'From header is correctly encoded.');
    $this->assertEquals('Drépal this is a very long test sentence to te <simpletest@example.com>', Unicode::mimeHeaderDecode($sent_message['headers']['From']), 'From header is correctly encoded.');
    $this->assertFalse(isset($sent_message['headers']['Reply-to']), 'Message reply-to is not set if not specified.');
    $this->assertFalse(isset($sent_message['headers']['Errors-To']), 'Errors-to header must not be set, it is deprecated.');

    // Test RFC-2822 rules are respected for 'display-name' component of
    // 'From:' header. Specials characters are not allowed, so randomly add one
    // of them to the site name and check the string is wrapped in quotes. Also
    // hardcode some double-quotes and backslash to validate these are escaped
    // properly too.
    $specials = '()<>[]:;@\,."';
    $site_name = 'Drupal' . $specials[rand(0, strlen($specials) - 1)] . ' "si\te"';
    $this->config('system.site')->set('name', $site_name)->save();
    // Send an email and check that the From-header contains the site name
    // within double-quotes. Also make sure double-quotes and "\" are escaped.
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'from_test', 'from_test@example.com', $language);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    $escaped_site_name = str_replace(['\\', '"'], ['\\\\', '\\"'], $site_name);
    $this->assertEquals('"' . $escaped_site_name . '" <simpletest@example.com>', $sent_message['headers']['From'], 'From header is correctly quoted.');

    // Make sure display-name is not quoted nor escaped if part on an encoding.
    $site_name = 'Drépal, "si\te"';
    $this->config('system.site')->set('name', $site_name)->save();
    // Send an email and check that the From-header contains the site name.
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'from_test', 'from_test@example.com', $language);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    $this->assertEquals('=?UTF-8?B?RHLDqXBhbCwgInNpXHRlIg==?= <simpletest@example.com>', $sent_message['headers']['From'], 'From header is correctly encoded.');
    $this->assertEquals($site_name . ' <simpletest@example.com>', Unicode::mimeHeaderDecode($sent_message['headers']['From']), 'From header is correctly encoded.');
  }

  /**
   * Checks that relative paths in mails are converted into absolute URLs.
   */
  public function testConvertRelativeUrlsIntoAbsolute() {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Use the HTML compatible state system collector mail backend.
    $this->config('system.mail')->set('interface.default', 'test_html_mail_collector')->save();

    // Fetch the hostname and port for matching against.
    $http_host = \Drupal::request()->getSchemeAndHttpHost();

    // Random generator.
    $random = new Random();

    // One random tag name.
    $tag_name = strtolower($random->name(8, TRUE));

    // Test root relative urls.
    foreach (['href', 'src'] as $attribute) {
      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      $html = "<$tag_name $attribute=\"/root-relative\">root relative url in mail test</$tag_name>";
      $expected_html = "<$tag_name $attribute=\"{$http_host}/root-relative\">root relative url in mail test</$tag_name>";

      // Prepare render array.
      $render = ['#markup' => Markup::create($html)];

      // Send a test message that simpletest_mail_alter should cancel.
      \Drupal::service('plugin.manager.mail')->mail('mail_html_test', 'render_from_message_param', 'relative_url@example.com', $language_interface->getId(), ['message' => $render]);
      // Retrieve sent message.
      $captured_emails = \Drupal::state()->get('system.test_mail_collector');
      $sent_message = end($captured_emails);

      // Wrap the expected HTML and assert.
      $expected_html = MailFormatHelper::wrapMail($expected_html);
      $this->assertSame($expected_html, $sent_message['body'], "Asserting that {$attribute} is properly converted for mails.");
    }

    // Test protocol relative urls.
    foreach (['href', 'src'] as $attribute) {
      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      $html = "<$tag_name $attribute=\"//example.com/protocol-relative\">protocol relative url in mail test</$tag_name>";
      $expected_html = "<$tag_name $attribute=\"//example.com/protocol-relative\">protocol relative url in mail test</$tag_name>";

      // Prepare render array.
      $render = ['#markup' => Markup::create($html)];

      // Send a test message that simpletest_mail_alter should cancel.
      \Drupal::service('plugin.manager.mail')->mail('mail_html_test', 'render_from_message_param', 'relative_url@example.com', $language_interface->getId(), ['message' => $render]);
      // Retrieve sent message.
      $captured_emails = \Drupal::state()->get('system.test_mail_collector');
      $sent_message = end($captured_emails);

      // Wrap the expected HTML and assert.
      $expected_html = MailFormatHelper::wrapMail($expected_html);
      $this->assertSame($expected_html, $sent_message['body'], "Asserting that {$attribute} is properly converted for mails.");
    }

    // Test absolute urls.
    foreach (['href', 'src'] as $attribute) {
      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      $html = "<$tag_name $attribute=\"http://example.com/absolute\">absolute url in mail test</$tag_name>";
      $expected_html = "<$tag_name $attribute=\"http://example.com/absolute\">absolute url in mail test</$tag_name>";

      // Prepare render array.
      $render = ['#markup' => Markup::create($html)];

      // Send a test message that simpletest_mail_alter should cancel.
      \Drupal::service('plugin.manager.mail')->mail('mail_html_test', 'render_from_message_param', 'relative_url@example.com', $language_interface->getId(), ['message' => $render]);
      // Retrieve sent message.
      $captured_emails = \Drupal::state()->get('system.test_mail_collector');
      $sent_message = end($captured_emails);

      // Wrap the expected HTML and assert.
      $expected_html = MailFormatHelper::wrapMail($expected_html);
      $this->assertSame($expected_html, $sent_message['body'], "Asserting that {$attribute} is properly converted for mails.");
    }
  }

  /**
   * Checks that mails built from render arrays contain absolute paths.
   *
   * By default Drupal uses relative paths for images and links. When sending
   * emails, absolute paths should be used instead.
   */
  public function testRenderedElementsUseAbsolutePaths() {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Use the HTML compatible state system collector mail backend.
    $this->config('system.mail')->set('interface.default', 'test_html_mail_collector')->save();

    // Fetch the hostname and port for matching against.
    $http_host = \Drupal::request()->getSchemeAndHttpHost();

    // Random generator.
    $random = new Random();
    $image_name = $random->name();

    // Create an image file.
    $file = File::create(['uri' => "public://{$image_name}.png", 'filename' => "{$image_name}.png"]);
    $file->save();

    $base_path = base_path();

    $path_pairs = [
      'root relative' => [$file->getFileUri(), "{$http_host}{$base_path}{$this->publicFilesDirectory}/{$image_name}.png"],
      'protocol relative' => ['//example.com/image.png', '//example.com/image.png'],
      'absolute' => ['http://example.com/image.png', 'http://example.com/image.png'],
    ];

    // Test images.
    foreach ($path_pairs as $test_type => $paths) {
      list($input_path, $expected_path) = $paths;

      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      // Build the render array.
      $render = [
        '#theme' => 'image',
        '#uri' => $input_path,
      ];
      $expected_html = "<img src=\"$expected_path\" alt=\"\" />";

      // Send a test message that simpletest_mail_alter should cancel.
      \Drupal::service('plugin.manager.mail')->mail('mail_html_test', 'render_from_message_param', 'relative_url@example.com', $language_interface->getId(), ['message' => $render]);
      // Retrieve sent message.
      $captured_emails = \Drupal::state()->get('system.test_mail_collector');
      $sent_message = end($captured_emails);

      // Wrap the expected HTML and assert.
      $expected_html = MailFormatHelper::wrapMail($expected_html);
      $this->assertSame($expected_html, $sent_message['body'], "Asserting that {$test_type} paths are converted properly.");
    }

    // Test links.
    $path_pairs = [
      'root relative' => [Url::fromUserInput('/path/to/something'), "{$http_host}{$base_path}path/to/something"],
      'protocol relative' => [Url::fromUri('//example.com/image.png'), '//example.com/image.png'],
      'absolute' => [Url::fromUri('http://example.com/image.png'), 'http://example.com/image.png'],
    ];

    foreach ($path_pairs as $paths) {
      list($input_path, $expected_path) = $paths;

      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      // Build the render array.
      $render = [
        '#title' => 'Link',
        '#type' => 'link',
        '#url' => $input_path,
      ];
      $expected_html = "<a href=\"$expected_path\">Link</a>";

      // Send a test message that simpletest_mail_alter should cancel.
      \Drupal::service('plugin.manager.mail')->mail('mail_html_test', 'render_from_message_param', 'relative_url@example.com', $language_interface->getId(), ['message' => $render]);
      // Retrieve sent message.
      $captured_emails = \Drupal::state()->get('system.test_mail_collector');
      $sent_message = end($captured_emails);

      // Wrap the expected HTML and assert.
      $expected_html = MailFormatHelper::wrapMail($expected_html);
      $this->assertSame($expected_html, $sent_message['body']);
    }
  }

}
