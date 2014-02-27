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
 * Tests the mail plugin manager.
 *
 * @group Drupal
 * @group Mail
 *
 * @see \Drupal\Core\Mail\MailManager
 */
class MailManagerTest extends UnitTestCase {

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

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
  public static function getInfo() {
    return array(
      'name' => 'Mail manager test',
      'description' => 'Tests the mail plugin manager.',
      'group' => 'Mail',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Prepare the default constructor arguments required by MailManager.
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue((object) array('id' => 'en')));

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
    $this->configFactory = $this->getConfigFactoryStub(array('system.mail' => array(
      'interface' => $interface,
    )));
    // Construct the manager object and override its discovery.
    $this->mailManager = new TestMailManager(new \ArrayObject(), $this->cache, $this->languageManager, $this->moduleHandler, $this->configFactory);
    $this->mailManager->setDiscovery($this->discovery);
  }

  /**
   * Tests the getInstance method.
   *
   * @covers \Drupal\Core\Mail\MailManager::getInstance()
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
