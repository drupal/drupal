<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Mail\MailManagerTest.
 */

namespace Drupal\Tests\Core\Mail;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Mail\MailManager;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Mail\MailManager
 * @group Mail
 */
class MailManagerTest extends UnitTestCase {

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $discovery;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * The mail manager under test.
   *
   * @var \Drupal\Tests\Core\Mail\TestMailManager
   */
  protected $mailManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $requestStack;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * A list of mail plugin definitions.
   *
   * @var array
   */
  protected $definitions = [
    'php_mail' => [
      'id' => 'php_mail',
      'class' => 'Drupal\Core\Mail\Plugin\Mail\PhpMail',
    ],
    'test_mail_collector' => [
      'id' => 'test_mail_collector',
      'class' => 'Drupal\Core\Mail\Plugin\Mail\TestMailCollector',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Prepare the default constructor arguments required by MailManager.
    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    // Mock a Discovery object to replace AnnotationClassDiscovery.
    $this->discovery = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->willReturn($this->definitions);
  }

  /**
   * Sets up the mail manager for testing.
   */
  protected function setUpMailManager($interface = []) {
    // Use the provided config for system.mail.interface settings.
    $this->configFactory = $this->getConfigFactoryStub([
      'system.mail' => [
        'interface' => $interface,
      ],
      'system.site' => [
        'mail' => 'test@example.com',
      ],
    ]);
    $logger_factory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $string_translation = $this->getStringTranslationStub();
    $this->renderer = $this->createMock(RendererInterface::class);
    // Construct the manager object and override its discovery.
    $this->mailManager = new TestMailManager(new \ArrayObject(), $this->cache, $this->moduleHandler, $this->configFactory, $logger_factory, $string_translation, $this->renderer);
    $this->mailManager->setDiscovery($this->discovery);

    $this->request = new Request();

    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->requestStack->getCurrentRequest()
      ->willReturn($this->request);

    // @see \Drupal\Core\Plugin\Factory\ContainerFactory::createInstance()
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory);
    $container->set('request_stack', $this->requestStack->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the getInstance method.
   *
   * @covers ::getInstance
   */
  public function testGetInstance() {
    $interface = [
      'default' => 'php_mail',
      'example_testkey' => 'test_mail_collector',
    ];
    $this->setUpMailManager($interface);

    // Test that an unmatched message_id returns the default plugin instance.
    $options = ['module' => 'foo', 'key' => 'bar'];
    $instance = $this->mailManager->getInstance($options);
    $this->assertInstanceOf('Drupal\Core\Mail\Plugin\Mail\PhpMail', $instance);

    // Test that a matching message_id returns the specified plugin instance.
    $options = ['module' => 'example', 'key' => 'testkey'];
    $instance = $this->mailManager->getInstance($options);
    $this->assertInstanceOf('Drupal\Core\Mail\Plugin\Mail\TestMailCollector', $instance);
  }

  /**
   * Tests that mails are sent in a separate render context.
   *
   * @covers ::mail
   */
  public function testMailInRenderContext() {
    $interface = [
      'default' => 'php_mail',
      'example_testkey' => 'test_mail_collector',
    ];
    $this->setUpMailManager($interface);

    $this->renderer->expects($this->exactly(1))
      ->method('executeInRenderContext')
      ->willReturnCallback(function (RenderContext $render_context, $callback) {
        $message = $callback();
        $this->assertEquals('example', $message['module']);
      });
    $this->mailManager->mail('example', 'key', 'to@example.org', 'en');
  }

}

/**
 * Provides a testing version of MailManager with an empty constructor.
 */
class TestMailManager extends MailManager {

  /**
   * Sets the discovery for the manager.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The discovery object.
   */
  public function setDiscovery(DiscoveryInterface $discovery) {
    $this->discovery = $discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function doMail($module, $key, $to, $langcode, $params = [], $reply = NULL, $send = TRUE) {
    // Build a simplified message array and return it.
    $message = [
      'id' => $module . '_' . $key,
      'module' => $module,
      'key' => $key,
      'to' => $to,
      'from' => 'from@example.org',
      'reply-to' => $reply,
      'langcode' => $langcode,
      'params' => $params,
      'send' => TRUE,
      'subject' => '',
      'body' => [],
    ];

    return $message;
  }

}
