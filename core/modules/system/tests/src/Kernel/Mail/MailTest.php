<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Mail;

use Drupal\Component\Utility\Random;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\Plugin\Mail\TestMailCollector;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system_mail_failure_test\Plugin\Mail\TestPhpMailFailure;

// cspell:ignore drépal

/**
 * Performs tests on the pluggable mailing framework.
 *
 * @group Mail
 */
class MailTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'mail_cancel_test',
    'mail_html_test',
    'system',
    'system_mail_failure_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installConfig(['system']);

    // Set required site configuration.
    $this->config('system.site')
      ->set('mail', 'mailtest@example.com')
      ->set('name', 'Drupal')
      ->save();
  }

  /**
   * Assert that the pluggable mail system is functional.
   */
  public function testPluggableFramework(): void {
    // Switch mail backends.
    $this->configureDefaultMailInterface('test_php_mail_failure');

    // Get the default MailInterface class instance.
    $mail_backend = \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'default', 'key' => 'default']);

    // Assert whether the default mail backend is an instance of the expected
    // class.
    // Default mail interface can be swapped.
    $this->assertInstanceOf(TestPhpMailFailure::class, $mail_backend);

    // Add a module-specific mail backend.
    $this->config('system.mail')->set('interface.my_module_test_key', 'test_mail_collector')->save();

    // Get the added MailInterface class instance.
    $mail_backend = \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'my_module', 'key' => 'test_key']);

    // Assert whether the added mail backend is an instance of the expected
    // class.
    // Additional mail interfaces can be added.
    $this->assertInstanceOf(TestMailCollector::class, $mail_backend);
  }

  /**
   * Assert that the pluggable mail system is functional.
   */
  public function testErrorMessageDisplay(): void {
    // Switch mail backends.
    $this->configureDefaultMailInterface('test_php_mail_failure');

    // Test with errors displayed to users.
    \Drupal::service('plugin.manager.mail')->mail('default', 'default', 'test@example.com', 'en');
    $messages = \Drupal::messenger()->messagesByType(MessengerInterface::TYPE_ERROR);
    $this->assertEquals('Unable to send email. Contact the site administrator if the problem persists.', $messages[0]);
    \Drupal::messenger()->deleteAll();

    // Test without errors displayed to users.
    \Drupal::service('plugin.manager.mail')->mail('default', 'default', 'test@example.com', 'en', ['_error_message' => '']);
    $this->assertEmpty(\Drupal::messenger()->messagesByType(MessengerInterface::TYPE_ERROR));
  }

  /**
   * Tests that message sending may be canceled.
   *
   * @see mail_cancel_test_mail_alter()
   */
  public function testCancelMessage(): void {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', []);

    // Send a test message that mail_cancel_test_alter should cancel.
    \Drupal::service('plugin.manager.mail')->mail('mail_cancel_test', 'cancel_test', 'cancel@example.com', $language_interface->getId());
    // Retrieve sent message.
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);

    // Assert that the message was not actually sent.
    // Message was canceled.
    $this->assertFalse($sent_message);
  }

  /**
   * Checks the From: and Reply-to: headers.
   */
  public function testFromAndReplyToHeader(): void {
    $language = \Drupal::languageManager()->getCurrentLanguage();

    // Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', []);
    // Send an email with a reply-to address specified.
    $from_email = 'Drupal <mailtest@example.com>';
    $reply_email = 'someone_else@example.com';
    \Drupal::service('plugin.manager.mail')->mail('mail_cancel_test', 'from_test', 'from_test@example.com', $language, [], $reply_email);
    // Test that the reply-to email is just the email and not the site name
    // and default sender email.
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    // Message is sent from the site email account.
    $this->assertEquals($from_email, $sent_message['headers']['From']);
    // Message reply-to headers are set.
    $this->assertEquals($reply_email, $sent_message['headers']['Reply-to']);
    // Errors-to header must not be set, it is deprecated.
    $this->assertFalse(isset($sent_message['headers']['Errors-To']));

    // Test that long site names containing characters that need MIME encoding
    // works as expected.
    $this->config('system.site')->set('name', 'Drépal this is a very long test sentence to test what happens with very long site names')->save();
    // Send an email and check that the From-header contains the site name.
    \Drupal::service('plugin.manager.mail')->mail('mail_cancel_test', 'from_test', 'from_test@example.com', $language);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    // From header is correctly encoded.
    $this->assertEquals('=?utf-8?Q?Dr=C3=A9pal?= this is a very long test sentence to test what happens with very long site names <mailtest@example.com>', $sent_message['headers']['From']);
    // From header is correctly encoded.
    $this->assertEquals('Drépal this is a very long test sentence to test what happens with very long site names <mailtest@example.com>', iconv_mime_decode($sent_message['headers']['From']));
    $this->assertFalse(isset($sent_message['headers']['Reply-to']), 'Message reply-to is not set if not specified.');
    // Errors-to header must not be set, it is deprecated.
    $this->assertFalse(isset($sent_message['headers']['Errors-To']));

    // Test that From names containing commas work as expected.
    $this->config('system.site')->set('name', 'Foo, Bar, and Baz')->save();
    // Send an email and check that the From-header contains the site name.
    \Drupal::service('plugin.manager.mail')->mail('mail_cancel_test', 'from_test', 'from_test@example.com', $language);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    // From header contains the quoted site name with commas.
    $this->assertEquals('"Foo, Bar, and Baz" <mailtest@example.com>', $sent_message['headers']['From']);

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
    \Drupal::service('plugin.manager.mail')->mail('mail_cancel_test', 'from_test', 'from_test@example.com', $language);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    $escaped_site_name = str_replace(['\\', '"'], ['\\\\', '\\"'], $site_name);
    // From header is correctly quoted.
    $this->assertEquals('"' . $escaped_site_name . '" <mailtest@example.com>', $sent_message['headers']['From']);

    // Make sure display-name is not quoted nor escaped if part on an encoding.
    $site_name = 'Drépal, "si\te"';
    $this->config('system.site')->set('name', $site_name)->save();
    // Send an email and check that the From-header contains the site name.
    \Drupal::service('plugin.manager.mail')->mail('mail_cancel_test', 'from_test', 'from_test@example.com', $language);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    // From header is correctly encoded.
    $this->assertEquals('=?utf-8?Q?Dr=C3=A9pal=2C_=22si=5Cte=22?= <mailtest@example.com>', $sent_message['headers']['From']);
    // From header is correctly encoded.
    $this->assertEquals($site_name . ' <mailtest@example.com>', iconv_mime_decode($sent_message['headers']['From']));
  }

  /**
   * Checks that relative paths in mails are converted into absolute URLs.
   */
  public function testConvertRelativeUrlsIntoAbsolute(): void {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    $this->configureDefaultMailInterface('test_html_mail_collector');

    // Fetch the hostname and port for matching against.
    $http_host = \Drupal::request()->getSchemeAndHttpHost();

    // Random generator.
    $random = new Random();

    // One random tag name.
    $tag_name = strtolower($random->name(8, TRUE));

    // Test root relative URLs.
    foreach (['href', 'src'] as $attribute) {
      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      $html = "<$tag_name $attribute=\"/root-relative\">root relative url in mail test</$tag_name>";
      $expected_html = "<$tag_name $attribute=\"{$http_host}/root-relative\">root relative url in mail test</$tag_name>";

      // Prepare render array.
      $render = ['#markup' => Markup::create($html)];

      // Send a test message that mail_cancel_test_mail_alter should cancel.
      \Drupal::service('plugin.manager.mail')->mail('mail_html_test', 'render_from_message_param', 'relative_url@example.com', $language_interface->getId(), ['message' => $render]);
      // Retrieve sent message.
      $captured_emails = \Drupal::state()->get('system.test_mail_collector');
      $sent_message = end($captured_emails);

      // Wrap the expected HTML and assert.
      $expected_html = MailFormatHelper::wrapMail($expected_html);
      $this->assertSame($expected_html, $sent_message['body'], "Asserting that {$attribute} is properly converted for mails.");
    }

    // Test protocol relative URLs.
    foreach (['href', 'src'] as $attribute) {
      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      $html = "<$tag_name $attribute=\"//example.com/protocol-relative\">protocol relative url in mail test</$tag_name>";
      $expected_html = "<$tag_name $attribute=\"//example.com/protocol-relative\">protocol relative url in mail test</$tag_name>";

      // Prepare render array.
      $render = ['#markup' => Markup::create($html)];

      // Send a test message that mail_cancel_test_mail_alter should cancel.
      \Drupal::service('plugin.manager.mail')->mail('mail_html_test', 'render_from_message_param', 'relative_url@example.com', $language_interface->getId(), ['message' => $render]);
      // Retrieve sent message.
      $captured_emails = \Drupal::state()->get('system.test_mail_collector');
      $sent_message = end($captured_emails);

      // Wrap the expected HTML and assert.
      $expected_html = MailFormatHelper::wrapMail($expected_html);
      $this->assertSame($expected_html, $sent_message['body'], "Asserting that {$attribute} is properly converted for mails.");
    }

    // Test absolute URLs.
    foreach (['href', 'src'] as $attribute) {
      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      $html = "<$tag_name $attribute=\"http://example.com/absolute\">absolute url in mail test</$tag_name>";
      $expected_html = "<$tag_name $attribute=\"http://example.com/absolute\">absolute url in mail test</$tag_name>";

      // Prepare render array.
      $render = ['#markup' => Markup::create($html)];

      // Send a test message that mail_cancel_test_mail_alter should cancel.
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
  public function testRenderedElementsUseAbsolutePaths(): void {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    $this->configureDefaultMailInterface('test_html_mail_collector');

    // Fetch the hostname and port for matching against.
    $http_host = \Drupal::request()->getSchemeAndHttpHost();

    // Random generator.
    $random = new Random();
    $image_name = $random->name();

    $test_base_url = 'http://localhost';
    $this->setSetting('file_public_base_url', $test_base_url);
    $filepath = \Drupal::service('file_system')->createFilename("{$image_name}.png", '');
    $directory_uri = 'public://' . dirname($filepath);
    \Drupal::service('file_system')->prepareDirectory($directory_uri, FileSystemInterface::CREATE_DIRECTORY);

    // Create an image file.
    $file = File::create(['uri' => "public://{$image_name}.png", 'filename' => "{$image_name}.png"]);
    $file->save();

    $base_path = base_path();

    $path_pairs = [
      'root relative' => [$file->getFileUri(), "{$http_host}{$base_path}{$image_name}.png"],
      'protocol relative' => ['//example.com/image.png', '//example.com/image.png'],
      'absolute' => ['http://example.com/image.png', 'http://example.com/image.png'],
    ];

    // Test images.
    foreach ($path_pairs as $test_type => $paths) {
      [$input_path, $expected_path] = $paths;

      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      // Build the render array.
      $render = [
        '#theme' => 'image',
        '#uri' => $input_path,
      ];
      $expected_html = "<img src=\"$expected_path\" alt>\n";

      // Send a test message that mail_cancel_test_mail_alter should cancel.
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
      [$input_path, $expected_path] = $paths;

      // Reset the state variable that holds sent messages.
      \Drupal::state()->set('system.test_mail_collector', []);

      // Build the render array.
      $render = [
        '#title' => 'Link',
        '#type' => 'link',
        '#url' => $input_path,
      ];
      $expected_html = "<a href=\"$expected_path\">Link</a>";

      // Send a test message that mail_cancel_test_mail_alter should cancel.
      \Drupal::service('plugin.manager.mail')->mail('mail_html_test', 'render_from_message_param', 'relative_url@example.com', $language_interface->getId(), ['message' => $render]);
      // Retrieve sent message.
      $captured_emails = \Drupal::state()->get('system.test_mail_collector');
      $sent_message = end($captured_emails);

      // Wrap the expected HTML and assert.
      $expected_html = MailFormatHelper::wrapMail($expected_html);
      $this->assertSame($expected_html, $sent_message['body']);
    }
  }

  /**
   * Configures the default mail interface.
   *
   * KernelTestBase enforces the usage of 'test_mail_collector' plugin to
   * collect mail. Since we need to test this functionality itself, we
   * manually configure the default mail interface.
   *
   * @todo Refactor in https://www.drupal.org/project/drupal/issues/3076715
   *
   * @param string $mail_interface
   *   The mail interface to configure.
   */
  protected function configureDefaultMailInterface($mail_interface) {
    $GLOBALS['config']['system.mail']['interface']['default'] = $mail_interface;
  }

}
