<?php

namespace Drupal\Tests\Core\Mail\Plugin\Mail;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ServerBag;

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
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $requestStack;

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

    $this->request = new Request();

    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->requestStack->getCurrentRequest()
      ->willReturn($this->request);

    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory);
    $container->set('request_stack', $this->requestStack->reveal());
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

    $request = $this->getMockBuilder(Request::class)
      ->disableOriginalConstructor()
      ->getMock();

    $request->server = $this->getMockBuilder(ServerBag::class)
      ->onlyMethods(['has', 'get'])
      ->getMock();

    $request->server->method('has')->willReturn(FALSE);
    $request->server->method('get')->willReturn(FALSE);

    $reflection = new \ReflectionClass($mailer);
    $reflection_property = $reflection->getProperty('request');
    $reflection_property->setValue($mailer, $request);
    return $mailer;
  }

  /**
   * Tests sending a mail using a From address with a comma in it.
   *
   * @covers ::mail
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
    $line_end = "\r\n";

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
