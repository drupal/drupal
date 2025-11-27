<?php

declare(strict_types=1);

namespace Drupal\Tests\mailer\Functional;

use Drupal\Core\Test\MailerCaptureTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Tests capturing of emails when mailer_capture module is installed.
 *
 * The mailer_capture module installs a mailer transport which intercepts any
 * email sent during a test.
 */
#[Group('mailer')]
#[RunTestsInSeparateProcesses]
class MailerCaptureTest extends BrowserTestBase {

  use MailerCaptureTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mailer', 'mailer_capture', 'mailer_capture_test'];

  /**
   * Tests collecting mail sent in the test runner.
   */
  public function testMailCaptureTestRunner(): void {
    // Create an email.
    $email = new Email();
    $email->from('admin@example.com');
    $email->subject('State machine energy a production like service.');
    $email->text('We name know environmental along agree let. Traditional interest this clearly concern discover.');

    // Before we send the email, getEmails should return an empty array.
    $capturedEmails = $this->getEmails();
    $this->assertCount(0, $capturedEmails, 'The captured emails queue is empty.');

    $mailer = $this->container->get(MailerInterface::class);
    assert($mailer instanceof MailerInterface);
    $mailer->send($email->to('foobar@example.com'));

    // Ensure that there is one email in the captured emails array.
    $capturedEmails = $this->getEmails();
    $this->assertCount(1, $capturedEmails, 'One email was captured.');
    $this->assertEquals([new Address('admin@example.com')], $capturedEmails[0]->getFrom());
    $this->assertEquals([new Address('foobar@example.com')], $capturedEmails[0]->getTo());
    $this->assertEquals('State machine energy a production like service.', $capturedEmails[0]->getSubject());
    $this->assertEquals(
      'We name know environmental along agree let. Traditional interest this clearly concern discover.',
      (string) $capturedEmails[0]->getTextBody()
    );

    $this->clearCapturedMessages();
    $capturedEmails = $this->getEmails();
    $this->assertCount(0, $capturedEmails, 'The captured emails queue is empty.');
  }

  /**
   * Tests collecting mail sent in the child site.
   */
  public function testMailCaptureChild(): void {
    // Before we send the email, getEmails should return an empty array.
    $capturedEmails = $this->getEmails();
    $this->assertCount(0, $capturedEmails, 'The captured emails queue is empty.');

    $this->drupalGet('/mailer-capture-test/send-mail');
    $this->submitForm([], 'Send Mail');

    // Ensure that there is one email in the captured emails array.
    $capturedEmails = $this->getEmails();

    $this->assertCount(1, $capturedEmails, 'One email was captured.');
    $this->assertEquals([new Address('admin@localhost.localdomain')], $capturedEmails[0]->getFrom());
    $this->assertEquals([new Address('test@localhost.localdomain')], $capturedEmails[0]->getTo());
    $this->assertEquals('Test message', $capturedEmails[0]->getSubject());
    $this->assertEquals('Hello test runner!', (string) $capturedEmails[0]->getTextBody());

    $this->clearCapturedMessages();
    $capturedEmails = $this->getEmails();
    $this->assertCount(0, $capturedEmails, 'The captured emails queue is empty.');
  }

}
