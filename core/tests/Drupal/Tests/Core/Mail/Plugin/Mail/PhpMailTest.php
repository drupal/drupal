<?php

namespace Drupal\Tests\Core\Mail\Plugin\Mail;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Mail\Plugin\Mail\PhpMail
 * @group Mail
 */
class PhpMailTest extends UnitTestCase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use the provided config for system.mail.interface settings.
    $this->configFactory = $this->getConfigFactoryStub([
      'system.mail' => [
        'interface' => [],
      ],
      'system.site' => [
        'mail' => 'test@example.com',
      ],
    ]);

    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Creates a mocked PhpMail object.
   *
   * The method "doMail()" gets overridden to avoid a mail() call in tests.
   *
   * @return \Drupal\Core\Mail\Plugin\Mail\PhpMail|\PHPUnit\Framework\MockObject\MockObject
   *   A PhpMail instance.
   */
  protected function createPhpMailInstance(): PhpMail {
    $mailer = $this->getMockBuilder(PhpMail::class)
      ->onlyMethods(['doMail'])
      ->getMock();

    return $mailer;
  }

  /**
   * Tests sending a mail using a From address with a comma in it.
   *
   * @covers ::testMail
   */
  public function testMail() {
    // Setup a mail message.
    $message = [
      'id' => 'example_key',
      'module' => 'example',
      'key' => 'key',
      'to' => 'to@example.org',
      'from' => 'from@example.org',
      'reply-to' => 'from@example.org',
      'langcode' => 'en',
      'params' => [],
      'send' => TRUE,
      'subject' => "test\r\nsubject",
      'body' => '',
      'headers' => [
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'From' => '"Foo, Bar, and Baz" <from@example.org>',
        'Reply-to' => 'from@example.org',
        'Return-Path' => 'from@example.org',
      ],
    ];

    $mailer = $this->createPhpMailInstance();

    // Verify we use line endings consistent with the PHP mail() function, which
    // changed with PHP 8. See:
    // - https://www.drupal.org/node/3270647
    // - https://bugs.php.net/bug.php?id=81158
    // Since Drupal 10+ does not support PHP < 8, the PHP version check in the next line can be removed in Drupal 10+.
    $line_end = PHP_MAJOR_VERSION < 8 ? "\n" : "\r\n";

    $expected_headers = "MIME-Version: 1.0$line_end";
    $expected_headers .= "Content-Type: text/plain; charset=UTF-8; format=flowed; delsp=yes$line_end";
    $expected_headers .= "Content-Transfer-Encoding: 8Bit$line_end";
    $expected_headers .= "X-Mailer: Drupal$line_end";
    $expected_headers .= "From: \"Foo, Bar, and Baz\" <from@example.org>$line_end";
    $expected_headers .= "Reply-to: from@example.org$line_end";

    $mailer->expects($this->once())->method('doMail')
      ->with(
        $this->equalTo('to@example.org'),
        $this->equalTo("=?utf-8?Q?test?={$line_end} =?utf-8?Q?subject?="),
        $this->equalTo(''),
        $this->stringStartsWith($expected_headers),
      )
      ->willReturn(TRUE);

    $this->assertTrue($mailer->mail($message));
  }

}
