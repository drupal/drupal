<?php

namespace Drupal\Tests\content_translation\Unit\Access;

use Drupal\content_translation\Access\ContentTranslationManageAccessCheck;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests for content translation manage check.
 *
 * @coversDefaultClass \Drupal\content_translation\Access\ContentTranslationManageAccessCheck
 * @group Access
 * @group content_translation
 */
class ContentTranslationManageAccessCheckTest extends UnitTestCase {

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheContextsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cacheContextsManager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->cacheContextsManager->method('assertValidTokens')->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $this->cacheContextsManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the create access method.
   *
   * @covers ::access
   */
  public function testCreateAccess() {
    // Set the mock translation handler.
    $translation_handler = $this->getMock('\Drupal\content_translation\ContentTranslationHandlerInterface');
    $translation_handler->expects($this->once())
      ->method('getTranslationAccess')
      ->will($this->returnValue(AccessResult::allowed()));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getHandler')
      ->withAnyParameters()
      ->will($this->returnValue($translation_handler));

    // Set our source and target languages.
    $source = 'en';
    $target = 'it';

    // Set the mock language manager.
    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->at(0))
      ->method('getLanguage')
      ->with($this->equalTo($source))
      ->will($this->returnValue(new Language(array('id' => 'en'))));
    $language_manager->expects($this->at(1))
      ->method('getLanguages')
      ->will($this->returnValue(array('en' => array(), 'it' => array())));
    $language_manager->expects($this->at(2))
      ->method('getLanguage')
      ->with($this->equalTo($source))
      ->will($this->returnValue(new Language(array('id' => 'en'))));
    $language_manager->expects($this->at(3))
      ->method('getLanguage')
      ->with($this->equalTo($target))
      ->will($this->returnValue(new Language(array('id' => 'it'))));

    // Set the mock entity. We need to use ContentEntityBase for mocking due to
    // issues with phpunit and multiple interfaces.
    $entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->once())
      ->method('getEntityTypeId');
    $entity->expects($this->once())
      ->method('getTranslationLanguages')
      ->with()
      ->will($this->returnValue(array()));
    $entity->expects($this->once())
      ->method('getCacheContexts')
      ->willReturn([]);
    $entity->expects($this->once())
      ->method('getCacheMaxAge')
      ->willReturn(Cache::PERMANENT);
    $entity->expects($this->once())
      ->method('getCacheTags')
      ->will($this->returnValue(array('node:1337')));
    $entity->expects($this->once())
      ->method('getCacheContexts')
      ->willReturn(array());

    // Set the route requirements.
    $route = new Route('test_route');
    $route->setRequirement('_access_content_translation_manage', 'create');

    // Set up the route match.
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->once())
      ->method('getParameter')
      ->with('node')
      ->will($this->returnValue($entity));

    // Set the mock account.
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');

    // The access check under test.
    $check = new ContentTranslationManageAccessCheck($entity_manager, $language_manager);

    // The request params.
    $language = 'en';
    $entity_type_id = 'node';

    $this->assertTrue($check->access($route, $route_match, $account, $source, $target, $language, $entity_type_id)->isAllowed(), "The access check matches");
  }

}
