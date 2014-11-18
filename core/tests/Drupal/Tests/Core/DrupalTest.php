<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DrupalTest.
 */

namespace Drupal\Tests\Core;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the Drupal class.
 *
 * @coversDefaultClass \Drupal
 * @group DrupalTest
 */
class DrupalTest extends UnitTestCase {

  /**
   * The mock container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $container;

  protected function setUp() {
    $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
      ->setMethods(array('get'))
      ->getMock();
  }

  /**
   * Tests the get/setContainer() method.
   *
   * @covers ::getContainer
   */
  public function testSetContainer() {
    \Drupal::setContainer($this->container);
    $this->assertSame($this->container, \Drupal::getContainer());
  }

  /**
   * Tests the service() method.
   *
   * @covers ::service
   */
  public function testService() {
    $this->setMockContainerService('test_service');
    $this->assertNotNull(\Drupal::service('test_service'));
  }

  /**
   * Tests the currentUser() method.
   *
   * @covers ::currentUser
   */
  public function testCurrentUser() {
    $this->setMockContainerService('current_user');
    $this->assertNotNull(\Drupal::currentUser());
  }

  /**
   * Tests the entityManager() method.
   *
   * @covers ::entityManager
   */
  public function testEntityManager() {
    $this->setMockContainerService('entity.manager');
    $this->assertNotNull(\Drupal::entityManager());
  }

  /**
   * Tests the database() method.
   *
   * @covers ::database
   */
  public function testDatabase() {
    $this->setMockContainerService('database');
    $this->assertNotNull(\Drupal::database());
  }

  /**
   * Tests the service() method.
   *
   * @covers ::cache
   */
  public function testCache() {
    $this->setMockContainerService('cache.test');
    $this->assertNotNull(\Drupal::cache('test'));
  }

  /**
   * Tests the keyValueExpirable() method.
   *
   * @covers ::keyValueExpirable
   */
  public function testKeyValueExpirable() {
    $keyvalue = $this->getMockBuilder('Drupal\Core\KeyValueStore\KeyValueExpirableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $keyvalue->expects($this->once())
      ->method('get')
      ->with('test_collection')
      ->will($this->returnValue(TRUE));
    $this->setMockContainerService('keyvalue.expirable', $keyvalue);

    $this->assertNotNull(\Drupal::keyValueExpirable('test_collection'));
  }

  /**
   * Tests the lock() method.
   *
   * @covers ::lock
   */
  public function testLock() {
    $this->setMockContainerService('lock');
    $this->assertNotNull(\Drupal::lock());
  }

  /**
   * Tests the config() method.
   *
   * @covers ::config
   */
  public function testConfig() {
    $config = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config->expects($this->once())
      ->method('get')
      ->with('test_config')
      ->will($this->returnValue(TRUE));
    $this->setMockContainerService('config.factory', $config);

    $this->assertNotNull(\Drupal::config('test_config'));
  }

  /**
   * Tests the queue() method.
   *
   * @covers ::queue
   */
  public function testQueue() {
    $queue = $this->getMockBuilder('Drupal\Core\Queue\QueueFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $queue->expects($this->once())
      ->method('get')
      ->with('test_queue', TRUE)
      ->will($this->returnValue(TRUE));
    $this->setMockContainerService('queue', $queue);

    $this->assertNotNull(\Drupal::queue('test_queue', TRUE));
  }

  /**
   * Tests the testRequestStack() method.
   *
   * @covers ::requestStack
   */
  public function testRequestStack() {
    $request_stack = new RequestStack();
    $this->setMockContainerService('request_stack', $request_stack);

    $this->assertSame($request_stack, \Drupal::requestStack());
  }

  /**
   * Tests the keyValue() method.
   *
   * @covers ::keyValue
   */
  public function testKeyValue() {
    $keyvalue = $this->getMockBuilder('Drupal\Core\KeyValueStore\KeyValueFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $keyvalue->expects($this->once())
      ->method('get')
      ->with('test_collection')
      ->will($this->returnValue(TRUE));
    $this->setMockContainerService('keyvalue', $keyvalue);

    $this->assertNotNull(\Drupal::keyValue('test_collection'));
  }

  /**
   * Tests the state() method.
   *
   * @covers ::state
   */
  public function testState() {
    $this->setMockContainerService('state');
    $this->assertNotNull(\Drupal::state());
  }

  /**
   * Tests the httpClient() method.
   *
   * @covers ::httpClient
   */
  public function testHttpClient() {
    $this->setMockContainerService('http_client');
    $this->assertNotNull(\Drupal::httpClient());
  }

  /**
   * Tests the entityQuery() method.
   *
   * @covers ::entityQuery
   */
  public function testEntityQuery() {
    $query = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->once())
      ->method('get')
      ->with('test_entity', 'OR')
      ->will($this->returnValue(TRUE));
    $this->setMockContainerService('entity.query', $query);

    $this->assertNotNull(\Drupal::entityQuery('test_entity', 'OR'));
  }

  /**
   * Tests the entityQueryAggregate() method.
   *
   * @covers ::entityQueryAggregate
   */
  public function testEntityQueryAggregate() {
    $query = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->once())
      ->method('getAggregate')
      ->with('test_entity', 'OR')
      ->will($this->returnValue(TRUE));
    $this->setMockContainerService('entity.query', $query);

    $this->assertNotNull(\Drupal::entityQueryAggregate('test_entity', 'OR'));
  }

  /**
   * Tests the flood() method.
   *
   * @covers ::flood
   */
  public function testFlood() {
    $this->setMockContainerService('flood');
    $this->assertNotNull(\Drupal::flood());
  }

  /**
   * Tests the moduleHandler() method.
   *
   * @covers ::moduleHandler
   */
  public function testModuleHandler() {
    $this->setMockContainerService('module_handler');
    $this->assertNotNull(\Drupal::moduleHandler());
  }

  /**
   * Tests the typedDataManager() method.
   *
   * @covers ::typedDataManager
   */
  public function testTypedDataManager() {
    $this->setMockContainerService('typed_data_manager');
    $this->assertNotNull(\Drupal::typedDataManager());
  }

  /**
   * Tests the token() method.
   *
   * @covers ::token
   */
  public function testToken() {
    $this->setMockContainerService('token');
    $this->assertNotNull(\Drupal::token());
  }

  /**
   * Tests the urlGenerator() method.
   *
   * @covers ::urlGenerator
   */
  public function testUrlGenerator() {
    $this->setMockContainerService('url_generator');
    $this->assertNotNull(\Drupal::urlGenerator());
  }

  /**
   * Tests the _url() method.
   *
   * @covers ::url
   * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute()
   */
  public function testUrl() {
    $route_parameters = array('test_parameter' => 'test');
    $options = array('test_option' => 'test');
    $generator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $generator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route', $route_parameters, $options)
      ->will($this->returnValue('path_string'));
    $this->setMockContainerService('url_generator', $generator);

    $this->assertInternalType('string', \Drupal::url('test_route', $route_parameters, $options));
  }

  /**
   * Tests the linkGenerator() method.
   *
   * @covers ::linkGenerator
   */
  public function testLinkGenerator() {
    $this->setMockContainerService('link_generator');
    $this->assertNotNull(\Drupal::linkGenerator());
  }

  /**
   * Tests the l() method.
   *
   * @covers ::l
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate()
   */
  public function testL() {
    $route_parameters = array('test_parameter' => 'test');
    $options = array('test_option' => 'test');
    $generator = $this->getMock('Drupal\Core\Utility\LinkGeneratorInterface');
    $url = new Url('test_route', $route_parameters, $options);
    $generator->expects($this->once())
      ->method('generate')
      ->with('Test title', $url)
      ->will($this->returnValue('link_html_string'));
    $this->setMockContainerService('link_generator', $generator);

    $this->assertInternalType('string', \Drupal::l('Test title', $url));
  }

  /**
   * Tests the translation() method.
   *
   * @covers ::translation
   */
  public function testTranslation() {
    $this->setMockContainerService('string_translation');
    $this->assertNotNull(\Drupal::translation());
  }

  /**
   * Tests the languageManager() method.
   *
   * @covers ::languageManager
   */
  public function testLanguageManager() {
    $this->setMockContainerService('language_manager');
    $this->assertNotNull(\Drupal::languageManager());
  }

  /**
   * Tests the csrfToken() method.
   *
   * @covers ::csrfToken
   */
  public function testCsrfToken() {
    $this->setMockContainerService('csrf_token');
    $this->assertNotNull(\Drupal::csrfToken());
  }

  /**
   * Tests the transliteration() method.
   *
   * @covers ::transliteration
   */
  public function testTransliteration() {
    $this->setMockContainerService('transliteration');
    $this->assertNotNull(\Drupal::transliteration());
  }

  /**
   * Tests the formBuilder() method.
   *
   * @covers ::formBuilder
   */
  public function testFormBuilder() {
    $this->setMockContainerService('form_builder');
    $this->assertNotNull(\Drupal::formBuilder());
  }

  /**
   * Tests the menuTree() method.
   *
   * @covers ::menuTree
   */
  public function testMenuTree() {
    $this->setMockContainerService('menu.link_tree');
    $this->assertNotNull(\Drupal::menuTree());
  }

  /**
   * Tests the pathValidator() method.
   *
   * @covers ::pathValidator
   */
  public function testPathValidator() {
    $this->setMockContainerService('path.validator');
    $this->assertNotNull(\Drupal::pathValidator());
  }

  /**
   * Tests the accessManager() method.
   *
   * @covers ::accessManager
   */
  public function testAccessManager() {
    $this->setMockContainerService('access_manager');
    $this->assertNotNull(\Drupal::accessManager());
  }

  /**
   * Sets up a mock expectation for the container get() method.
   *
   * @param string $service_name
   *   The service name to expect for the get() method.
   * @param mixed $return
   *   The value to return from the mocked container get() method.
   */
  protected function setMockContainerService($service_name, $return = NULL) {
    $expects = $this->container->expects($this->once())
      ->method('get')
      ->with($service_name);

    if (isset($return)) {
      $expects->will($this->returnValue($return));
    }
    else {
      $expects->will($this->returnValue(TRUE));
    }

    \Drupal::setContainer($this->container);
  }

}
