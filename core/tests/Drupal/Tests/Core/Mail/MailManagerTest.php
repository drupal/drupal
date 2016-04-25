<?php
/**
 * @file
 * Contains \Drupal\Tests\Core\Mail\MailManagerTest.
 */

namespace Drupal\Tests\Core\Mail;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Mail\MailManager;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * @coversDefaultClass \Drupal\Core\Mail\MailManager
 * @group Mail
 */
class MailManagerTest extends UnitTestCase {

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $discovery;

  /**
   * The mail manager under test.
   *
   * @var \Drupal\Tests\Core\Mail\TestMailManager
   */
  protected $mailManager;

  /**
   * A list of mail plugin definitions.
   *
   * @var array
   */
  protected $definitions = array(
    'php_mail' => array(
      'id' => 'php_mail',
      'class' => 'Drupal\Core\Mail\Plugin\Mail\PhpMail',
    ),
    'test_mail_collector' => array(
      'id' => 'test_mail_collector',
      'class' => 'Drupal\Core\Mail\Plugin\Mail\TestMailCollector',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Prepare the default constructor arguments required by MailManager.
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    // Mock a Discovery object to replace AnnotationClassDiscovery.
    $this->discovery = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($this->definitions));
  }

  /**
   * Sets up the mail manager for testing.
   */
  protected function setUpMailManager($interface = array()) {
    // Use the provided config for system.mail.interface settings.
    $this->configFactory = $this->getConfigFactoryStub(array(
      'system.mail' => array(
        'interface' => $interface,
      ),
    ));
    $logger_factory = $this->getMock('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $string_translation = $this->getStringTranslationStub();
    // Construct the manager object and override its discovery.
    $this->mailManager = new TestMailManager(new \ArrayObject(), $this->cache, $this->moduleHandler, $this->configFactory, $logger_factory, $string_translation);
    $this->mailManager->setDiscovery($this->discovery);
  }

  /**
   * Tests the getInstance method.
   *
   * @covers ::getInstance
   */
  public function testGetInstance() {
    $interface = array(
      'default' => 'php_mail',
      'example_testkey' => 'test_mail_collector',
    );
    $this->setUpMailManager($interface);

    // Test that an unmatched message_id returns the default plugin instance.
    $options = array('module' => 'foo', 'key' => 'bar');
    $instance = $this->mailManager->getInstance($options);
    $this->assertInstanceOf('Drupal\Core\Mail\Plugin\Mail\PhpMail', $instance);

    // Test that a matching message_id returns the specified plugin instance.
    $options = array('module' => 'example', 'key' => 'testkey');
    $instance = $this->mailManager->getInstance($options);
    $this->assertInstanceOf('Drupal\Core\Mail\Plugin\Mail\TestMailCollector', $instance);
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
}
