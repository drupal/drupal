<?php

declare(strict_types=1);

namespace Drupal\Tests\Core;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the Drupal class.
 */
#[CoversClass(\Drupal::class)]
#[Group('DrupalTest')]
class DrupalTest extends UnitTestCase {

  /**
   * The mock container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
      ->onlyMethods(['get'])
      ->getMock();
  }

  /**
   * Tests the get/setContainer() method.
   *
   * @legacy-covers ::getContainer
   */
  public function testSetContainer(): void {
    \Drupal::setContainer($this->container);
    $this->assertSame($this->container, \Drupal::getContainer());
  }

  /**
   * Tests get container exception.
   */
  public function testGetContainerException(): void {
    $this->expectException(ContainerNotInitializedException::class);
    $this->expectExceptionMessage('\Drupal::$container is not initialized yet. \Drupal::setContainer() must be called with a real container.');
    \Drupal::getContainer();
  }

  /**
   * Tests the service() method.
   */
  public function testService(): void {
    $this->setMockContainerService('test_service');
    $this->assertNotNull(\Drupal::service('test_service'));
  }

  /**
   * Tests the currentUser() method.
   */
  public function testCurrentUser(): void {
    $this->setMockContainerService('current_user');
    $this->assertNotNull(\Drupal::currentUser());
  }

  /**
   * Tests the entityTypeManager() method.
   */
  public function testEntityTypeManager(): void {
    $this->setMockContainerService('entity_type.manager');
    $this->assertNotNull(\Drupal::entityTypeManager());
  }

  /**
   * Tests the database() method.
   */
  public function testDatabase(): void {
    $this->setMockContainerService('database');
    $this->assertNotNull(\Drupal::database());
  }

  /**
   * Tests the cache() method.
   */
  public function testCache(): void {
    $this->setMockContainerService('cache.test');
    $this->assertNotNull(\Drupal::cache('test'));
  }

  /**
   * Tests the classResolver method.
   */
  public function testClassResolver(): void {
    $class_resolver = $this->prophesize(ClassResolverInterface::class);
    $this->setMockContainerService('class_resolver', $class_resolver->reveal());
    $this->assertInstanceOf(ClassResolverInterface::class, \Drupal::classResolver());
  }

  /**
   * Tests the classResolver method when called with a class.
   */
  public function testClassResolverWithClass(): void {
    $class_resolver = $this->prophesize(ClassResolverInterface::class);
    $class_resolver->getInstanceFromDefinition(static::class)->willReturn($this);
    $this->setMockContainerService('class_resolver', $class_resolver->reveal());
    $this->assertSame($this, \Drupal::classResolver(static::class));
  }

  /**
   * Tests the keyValueExpirable() method.
   */
  public function testKeyValueExpirable(): void {
    $keyvalue = $this->getMockBuilder('Drupal\Core\KeyValueStore\KeyValueExpirableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $keyvalue->expects($this->once())
      ->method('get')
      ->with('test_collection')
      ->willReturn(TRUE);
    $this->setMockContainerService('keyvalue.expirable', $keyvalue);

    $this->assertNotNull(\Drupal::keyValueExpirable('test_collection'));
  }

  /**
   * Tests the lock() method.
   */
  public function testLock(): void {
    $this->setMockContainerService('lock');
    $this->assertNotNull(\Drupal::lock());
  }

  /**
   * Tests the config() method.
   */
  public function testConfig(): void {
    $config = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config->expects($this->once())
      ->method('get')
      ->with('test_config')
      ->willReturn(TRUE);
    $this->setMockContainerService('config.factory', $config);

    // Test \Drupal::config(), not $this->config().
    $this->assertNotNull(\Drupal::config('test_config'));
  }

  /**
   * Tests the queue() method.
   */
  public function testQueue(): void {
    $queue = $this->getMockBuilder('Drupal\Core\Queue\QueueFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $queue->expects($this->once())
      ->method('get')
      ->with('test_queue', TRUE)
      ->willReturn(TRUE);
    $this->setMockContainerService('queue', $queue);

    $this->assertNotNull(\Drupal::queue('test_queue', TRUE));
  }

  /**
   * Tests the testRequestStack() method.
   */
  public function testRequestStack(): void {
    $request_stack = new RequestStack();
    $this->setMockContainerService('request_stack', $request_stack);

    $this->assertSame($request_stack, \Drupal::requestStack());
  }

  /**
   * Tests the keyValue() method.
   */
  public function testKeyValue(): void {
    $keyvalue = $this->getMockBuilder('Drupal\Core\KeyValueStore\KeyValueFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $keyvalue->expects($this->once())
      ->method('get')
      ->with('test_collection')
      ->willReturn(TRUE);
    $this->setMockContainerService('keyvalue', $keyvalue);

    $this->assertNotNull(\Drupal::keyValue('test_collection'));
  }

  /**
   * Tests the state() method.
   */
  public function testState(): void {
    $this->setMockContainerService('state');
    $this->assertNotNull(\Drupal::state());
  }

  /**
   * Tests the httpClient() method.
   */
  public function testHttpClient(): void {
    $this->setMockContainerService('http_client');
    $this->assertNotNull(\Drupal::httpClient());
  }

  /**
   * Tests the entityQuery() method.
   */
  public function testEntityQuery(): void {
    $query = $this->createMock(QueryInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage
      ->expects($this->once())
      ->method('getQuery')
      ->with('OR')
      ->willReturn($query);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->expects($this->once())
      ->method('getStorage')
      ->with('test_entity')
      ->willReturn($storage);

    $this->setMockContainerService('entity_type.manager', $entity_type_manager);

    $this->assertInstanceOf(QueryInterface::class, \Drupal::entityQuery('test_entity', 'OR'));
  }

  /**
   * Tests the entityQueryAggregate() method.
   */
  public function testEntityQueryAggregate(): void {
    $query = $this->createMock(QueryAggregateInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage
      ->expects($this->once())
      ->method('getAggregateQuery')
      ->with('OR')
      ->willReturn($query);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->expects($this->once())
      ->method('getStorage')
      ->with('test_entity')
      ->willReturn($storage);

    $this->setMockContainerService('entity_type.manager', $entity_type_manager);

    $this->assertInstanceOf(QueryAggregateInterface::class, \Drupal::entityQueryAggregate('test_entity', 'OR'));
  }

  /**
   * Tests the flood() method.
   */
  public function testFlood(): void {
    $this->setMockContainerService('flood');
    $this->assertNotNull(\Drupal::flood());
  }

  /**
   * Tests the moduleHandler() method.
   */
  public function testModuleHandler(): void {
    $this->setMockContainerService('module_handler');
    $this->assertNotNull(\Drupal::moduleHandler());
  }

  /**
   * Tests the typedDataManager() method.
   */
  public function testTypedDataManager(): void {
    $this->setMockContainerService('typed_data_manager');
    $this->assertNotNull(\Drupal::typedDataManager());
  }

  /**
   * Tests the token() method.
   */
  public function testToken(): void {
    $this->setMockContainerService('token');
    $this->assertNotNull(\Drupal::token());
  }

  /**
   * Tests the urlGenerator() method.
   */
  public function testUrlGenerator(): void {
    $this->setMockContainerService('url_generator');
    $this->assertNotNull(\Drupal::urlGenerator());
  }

  /**
   * Tests the linkGenerator() method.
   */
  public function testLinkGenerator(): void {
    $this->setMockContainerService('link_generator');
    $this->assertNotNull(\Drupal::linkGenerator());
  }

  /**
   * Tests the translation() method.
   */
  public function testTranslation(): void {
    $this->setMockContainerService('string_translation');
    $this->assertNotNull(\Drupal::translation());
  }

  /**
   * Tests the languageManager() method.
   */
  public function testLanguageManager(): void {
    $this->setMockContainerService('language_manager');
    $this->assertNotNull(\Drupal::languageManager());
  }

  /**
   * Tests the csrfToken() method.
   */
  public function testCsrfToken(): void {
    $this->setMockContainerService('csrf_token');
    $this->assertNotNull(\Drupal::csrfToken());
  }

  /**
   * Tests the transliteration() method.
   */
  public function testTransliteration(): void {
    $this->setMockContainerService('transliteration');
    $this->assertNotNull(\Drupal::transliteration());
  }

  /**
   * Tests the formBuilder() method.
   */
  public function testFormBuilder(): void {
    $this->setMockContainerService('form_builder');
    $this->assertNotNull(\Drupal::formBuilder());
  }

  /**
   * Tests the menuTree() method.
   */
  public function testMenuTree(): void {
    $this->setMockContainerService('menu.link_tree');
    $this->assertNotNull(\Drupal::menuTree());
  }

  /**
   * Tests the pathValidator() method.
   */
  public function testPathValidator(): void {
    $this->setMockContainerService('path.validator');
    $this->assertNotNull(\Drupal::pathValidator());
  }

  /**
   * Tests the accessManager() method.
   */
  public function testAccessManager(): void {
    $this->setMockContainerService('access_manager');
    $this->assertNotNull(\Drupal::accessManager());
  }

  /**
   * Tests the PHP constants have consistent values.
   */
  public function testPhpConstants(): void {
    // RECOMMENDED_PHP can be just MAJOR.MINOR so normalize it to allow using
    // version_compare().
    $normalizer = function (string $version): string {
      // The regex below is from \Composer\Semver\VersionParser::normalize().
      preg_match('{^(\d{1,5})(\.\d++)?(\.\d++)?$}i', $version, $matches);
      return $matches[1]
        . (!empty($matches[2]) ? $matches[2] : '.9999999')
        . (!empty($matches[3]) ? $matches[3] : '.9999999');
    };

    $recommended_php = $normalizer(\Drupal::RECOMMENDED_PHP);
    $this->assertTrue(version_compare($recommended_php, \Drupal::MINIMUM_PHP, '>='), "\Drupal::RECOMMENDED_PHP should be greater or equal to \Drupal::MINIMUM_PHP");

    // As this test depends on the $normalizer function it is tested.
    $this->assertSame('10.9999999.9999999', $normalizer('10'));
    $this->assertSame('10.1.9999999', $normalizer('10.1'));
    $this->assertSame('10.1.2', $normalizer('10.1.2'));
  }

  /**
   * Sets up a mock expectation for the container get() method.
   *
   * @param string $service_name
   *   The service name to expect for the get() method.
   * @param mixed $return
   *   The value to return from the mocked container get() method.
   */
  protected function setMockContainerService($service_name, $return = NULL): void {
    $this->container->expects($this->once())
      ->method('get')
      ->with($service_name)
      ->willReturn($return ?? new \stdClass());

    \Drupal::setContainer($this->container);
  }

}
