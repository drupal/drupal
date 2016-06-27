<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

/**
 * Tests URL handling of the \Drupal\Core\Entity\Entity class.
 *
 * @coversDefaultClass \Drupal\Core\Entity\Entity
 *
 * @group Entity
 */
class EntityUrlTest extends UnitTestCase {

  /**
   * The entity manager mock used in this test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The ID of the entity type used in this test.
   *
   * @var string
   */
  protected $entityTypeId = 'test_entity';

  /**
   * The entity type mock used in this test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The ID of the entity used in this test.
   *
   * @var int
   */
  protected $entityId = 1;

  /**
   * The revision ID of the entity used in this test.
   *
   * @var int
   */
  protected $revisionId = 2;

  /**
   * The language code of the entity used in this test.
   *
   * @var string
   */
  protected $langcode = 'en';

  /**
   * Indicator for default revisions.
   *
   * @var true
   */
  const DEFAULT_REVISION = TRUE;

  /**
   * Indicator for non-default revisions.
   *
   * @var false
   */
  const NON_DEFAULT_REVISION = FALSE;

  /**
   * Tests the toUrl() method without an entity ID.
   *
   * @covers ::toUrl
   */
  public function testToUrlNoId() {
    $entity = $this->getEntity(Entity::class, []);

    $this->setExpectedException(EntityMalformedException::class, 'The "' . $this->entityTypeId . '" entity cannot have a URI as it does not have an ID');
    $entity->toUrl();
  }

  /**
   * Tests the toUrl() method with simple link templates.
   *
   * @param string $link_template
   *   The link template to test.
   * @param string $expected_route_name
   *   The expected route name of the generated URL.
   *
   * @dataProvider providerTestToUrlLinkTemplates
   *
   * @covers ::toUrl
   * @covers ::linkTemplates
   * @covers ::urlRouteParameters
   */
  public function testToUrlLinkTemplates($link_template, $expected_route_name) {
    $values = ['id' => $this->entityId, 'langcode' => $this->langcode];
    $entity = $this->getEntity(Entity::class, $values);
    $this->registerLinkTemplate($link_template);

    /** @var \Drupal\Core\Url $url */
    $url = $entity->toUrl($link_template);
    // The entity ID is the sole route parameter for the link templates tested
    // here.
    $this->assertUrl($expected_route_name, ['test_entity' => $this->entityId], $entity, TRUE, $url);
  }

  /**
   * Provides data for testToUrlLinkTemplates().
   *
   * @return array
   *   An array of test cases for testToUrlLinkTemplates().
   */
  public function providerTestToUrlLinkTemplates() {
    $test_cases = [];

    $test_cases['canonical'] = ['canonical', 'entity.test_entity.canonical'];
    $test_cases['version-history'] = ['version-history', 'entity.test_entity.version_history'];
    $test_cases['edit-form'] = ['edit-form', 'entity.test_entity.edit_form'];
    $test_cases['delete-form'] = ['delete-form', 'entity.test_entity.delete_form'];
    $test_cases['revision'] = ['revision', 'entity.test_entity.revision'];

    return $test_cases;
  }

  /**
   * Tests the toUrl() method with the 'revision' link template.
   *
   * @param bool $is_default_revision
   *   Whether or not the mock entity should be the default revision.
   * @param string $link_template
   *   The link template to test.
   * @param string $expected_route_name
   *   The expected route name of the generated URL.
   * @param array $expected_route_parameters
   *   The expected route parameters of the generated URL.
   *
   * @dataProvider providerTestToUrlLinkTemplateRevision
   *
   * @covers ::toUrl
   * @covers ::linkTemplates
   * @covers ::urlRouteParameters
   */
  public function testToUrlLinkTemplateRevision($is_default_revision, $link_template, $expected_route_name, array $expected_route_parameters) {
    $values = ['id' => $this->entityId, 'langcode' => $this->langcode];
    $entity = $this->getEntity(RevisionableEntity::class, $values);
    $entity->method('getRevisionId')->willReturn($this->revisionId);
    $entity->method('isDefaultRevision')->willReturn($is_default_revision);
    $this->registerLinkTemplate($link_template);
    // Even though this is tested with both the 'canonical' and the 'revision'
    // template registered with the entity, we always ask for the 'revision'
    // link template, to test that it falls back to the 'canonical' link
    // template in case of the default revision.
    /** @var \Drupal\Core\Url $url */
    $url = $entity->toUrl('revision');
    $this->assertUrl($expected_route_name, $expected_route_parameters, $entity, TRUE, $url);

  }

  /**
   * Provides data for testToUrlLinkTemplateRevision().
   *
   * @return array
   *   An array of test cases for testToUrlLinkTemplateRevision().
   */
  public function providerTestToUrlLinkTemplateRevision() {
    $test_cases = [];

    $route_parameters = ['test_entity' => $this->entityId];
    $test_cases['default_revision'] = [static::DEFAULT_REVISION, 'canonical', 'entity.test_entity.canonical', $route_parameters];
    // Add the revision ID to the expected route parameters.
    $route_parameters['test_entity_revision'] = $this->revisionId;
    $test_cases['non_default_revision'] = [static::NON_DEFAULT_REVISION, 'revision', 'entity.test_entity.revision', $route_parameters];

    return $test_cases;
  }

  /**
   * Tests the toUrl() method with the 'collection' link template.
   *
   * @covers ::toUrl
   * @covers ::linkTemplates
   * @covers ::urlRouteParameters
   */
  public function testToUrlLinkTemplateCollection() {
    $entity = $this->getEntity(Entity::class, ['id' => $this->entityId]);
    $link_template = 'collection';
    $this->registerLinkTemplate($link_template);

    /** @var \Drupal\Core\Url $url */
    $url = $entity->toUrl($link_template);
    $this->assertUrl('entity.test_entity.collection', [], $entity, FALSE, $url);
  }

  /**
   * Tests the toUrl() method with neither link templates nor a URI callback.
   *
   * @param array $bundle_info
   *   An array of bundle info to register.
   * @param string $uri_callback
   *   The entity type URI callback to register.
   *
   * @dataProvider providerTestToUrlUriCallbackUndefined
   *
   * @covers ::toUrl
   * @covers ::linkTemplates
   */
  public function testToUrlUriCallbackUndefined(array $bundle_info, $uri_callback) {
    $entity = $this->getEntity(Entity::class, ['id' => $this->entityId]);

    $this->registerBundleInfo($bundle_info);
    $this->entityType->getUriCallback()->willReturn($uri_callback);

    $link_template = 'canonical';
    $this->setExpectedException(UndefinedLinkTemplateException::class, "No link template '$link_template' found for the '$this->entityTypeId' entity type");
    $entity->toUrl($link_template);
  }

  /**
   * Provides data for testToUrlUriCallbackUndefined().
   *
   * @return array
   *   An array of test cases for testToUrlUriCallbackUndefined().
   */
  public function providerTestToUrlUriCallbackUndefined() {
    $test_cases = [];

    $test_cases['no_callback'] = [[], NULL];
    $test_cases['uri_callback'] = [[], 'not_a_callable'];
    $test_cases['bundle_uri_callback'] = [['uri_callback' => 'not_a_callable'], NULL];

    return $test_cases;
  }

  /**
   * Tests the toUrl() method with a URI callback.
   *
   * @param array $bundle_info
   *   An array of bundle info to register.
   * @param string $uri_callback
   *   The entity type URI callback to register.
   *
   * @covers ::toUrl
   * @covers ::linkTemplates
   *
   * @dataProvider providerTestToUrlUriCallback
   */
  public function testToUrlUriCallback(array $bundle_info, $uri_callback) {
    $entity = $this->getEntity(Entity::class, ['id' => $this->entityId, 'langcode' => $this->langcode]);

    $this->registerBundleInfo($bundle_info);
    $this->entityType->getUriCallback()->willReturn($uri_callback);

    /** @var \Drupal\Core\Url $url */
    $url = $entity->toUrl('canonical');
    $this->assertUrl('<none>', [], $entity, TRUE, $url);
  }

  /**
   * Provides data for testToUrlUriCallback().
   *
   * @return array
   *   An array of test cases for testToUrlUriCallback().
   */
  public function providerTestToUrlUriCallback() {
    $test_cases = [];

    $uri_callback = function () { return Url::fromRoute('<none>'); };
    $test_cases['uri_callback'] = [[], $uri_callback];
    $test_cases['bundle_uri_callback'] = [['uri_callback' => $uri_callback], NULL];

    return $test_cases;
  }

  /**
   * Tests the urlInfo() method.
   *
   * @param string $rel
   *   The link relation to test.
   * @param array $options
   *   An array of URL options to test with.
   *
   * @covers ::urlInfo
   *
   * @dataProvider providerTestUrlInfo
   */
  public function testUrlInfo($rel, $options) {
    $entity = $this->getEntity(Entity::class, [], ['toUrl']);
    $entity->expects($this->once())
      ->method('toUrl')
      ->with($rel, $options);

    $entity->urlInfo($rel, $options);
  }

  /**
   * Provides data for testUrlInfo().
   *
   * @return array
   *   An array of test cases for testUrlInfo().
   */
  public function providerTestUrlInfo() {
    $test_cases = [];

    $test_cases['default'] = ['canonical', []];
    $test_cases['with_option'] = ['canonical', ['absolute' => TRUE]];
    $test_cases['revision'] = ['revision', []];

    return $test_cases;
  }

  /**
   * Tests the url() method without an entity ID.
   *
   * @param string $rel
   *   The link relation to test.
   *
   * @covers ::url
   * @covers ::hasLinkTemplate
   * @covers ::linkTemplates
   *
   * @dataProvider providerTestUrl
   */
  public function testUrlEmpty($rel) {
    $entity = $this->getEntity(Entity::class, []);
    $this->assertEquals('', $entity->url($rel));
  }

  /**
   * Provides data for testUrlEmpty().
   *
   * @return array
   *   An array of test cases for testUrlEmpty().
   */
  public function providerTestUrlEmpty() {
    $test_cases = [];

    $test_cases['default'] = ['canonical', []];
    $test_cases['revision'] = ['revision', []];

    return $test_cases;
  }

  /**
   * Tests the url() method.
   *
   * @param string $rel
   *   The link relation to test.
   * @param array $options
   *   An array of URL options to call url() with.
   * @param array $default_options
   *   An array of URL options that toUrl() should generate.
   * @param array $expected_options
   *   An array of combined URL options that should be set on the final URL.
   *
   * @covers ::url
   * @covers ::hasLinkTemplate
   * @covers ::linkTemplates
   *
   * @dataProvider providerTestUrl
   */
  public function testUrl($rel, $options, $default_options, $expected_options) {
    $entity = $this->getEntity(Entity::class, ['id' => $this->entityId], ['toUrl']);
    $this->registerLinkTemplate($rel);

    $uri = $this->prophesize(Url::class);
    $uri->getOptions()->willReturn($default_options);
    $uri->setOptions($expected_options)->shouldBeCalled();

    $url_string = "/test-entity/{$this->entityId}/$rel";
    $uri->toString()->willReturn($url_string);

    $entity->expects($this->once())
      ->method('toUrl')
      ->with($rel)
      ->willReturn($uri->reveal());

    $this->assertEquals($url_string, $entity->url($rel, $options));
  }

  /**
   * Provides data for testUrl().
   *
   * @return array
   *   An array of test cases for testUrl().
   */
  public function providerTestUrl() {
    $test_cases = [];

    $test_cases['default'] = ['canonical', [], [], []];
    $test_cases['revision'] = ['revision', [], [], []];
    $test_cases['option'] = ['canonical', ['absolute' => TRUE], [], ['absolute' => TRUE]];
    $test_cases['default_option'] = ['canonical', [], ['absolute' => TRUE], ['absolute' => TRUE]];
    $test_cases['option_merge'] = ['canonical', ['absolute' => TRUE], ['entity_type' => $this->entityTypeId], ['absolute' => TRUE, 'entity_type' => $this->entityTypeId]];
    $test_cases['option_override'] = ['canonical', ['absolute' => TRUE], ['absolute' => FALSE], ['absolute' => TRUE]];

    return $test_cases;
  }

  /**
   * Returns a mock entity for testing.
   *
   * @param string $class
   *   The class name to mock. Should be \Drupal\Core\Entity\Entity or a
   *   subclass.
   * @param array $values
   *   An array of entity values to construct the mock entity with.
   * @param array $methods
   *   (optional) An array of additional methods to mock on the entity object.
   *   The getEntityType() and entityManager() methods are always mocked.
   *
   * @return \Drupal\Core\Entity\Entity|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getEntity($class, array $values, array $methods = []) {
    $methods = array_merge($methods, ['getEntityType', 'entityManager']);

    // Prophecy does not allow prophesizing abstract classes while actually
    // calling their code. We use Prophecy below because that allows us to
    // add method prophecies later while still revealing the prophecy now.
    $entity = $this->getMockBuilder($class)
      ->setConstructorArgs([$values, $this->entityTypeId])
      ->setMethods($methods)
      ->getMockForAbstractClass();

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityType->getLinkTemplates()->willReturn([]);
    $this->entityType->getKey('langcode')->willReturn(FALSE);
    $entity->method('getEntityType')->willReturn($this->entityType->reveal());

    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
    $entity->method('entityManager')->willReturn($this->entityManager->reveal());

    return $entity;
  }

  /**
   * Asserts that a given URL object matches the expectations.
   *
   * @param string $expected_route_name
   *   The expected route name of the generated URL.
   * @param array $expected_route_parameters
   *   The expected route parameters of the generated URL.
   * @param \Drupal\Core\Entity\Entity|\PHPUnit_Framework_MockObject_MockObject $entity
   *   The entity that is expected to be set as a URL option.
   * @param bool $has_language
   *   Whether or not the URL is expected to have a language option.
   * @param \Drupal\Core\Url $url
   *   The URL option to make the assertions on.
   */
  protected function assertUrl($expected_route_name, array $expected_route_parameters, $entity, $has_language, Url $url) {
    $this->assertEquals($expected_route_name, $url->getRouteName());
    $this->assertEquals($expected_route_parameters, $url->getRouteParameters());
    $this->assertEquals($this->entityTypeId, $url->getOption('entity_type'));
    $this->assertEquals($entity, $url->getOption('entity'));
    if ($has_language) {
      $this->assertEquals($this->langcode, $url->getOption('language')->getId());
    }
    else {
      $this->assertNull($url->getOption('language'));
    }
  }

  /**
   * Registers a link template for the mock entity.
   *
   * @param string $link_template
   *   The link template to register.
   */
  protected function registerLinkTemplate($link_template) {
    $link_templates = [
      // The path is actually never used because we never invoke the URL
      // generator but perform assertions on the URL object directly.
      $link_template => "/test-entity/{test_entity}/$link_template",
    ];
    $this->entityType->getLinkTemplates()->willReturn($link_templates);
  }

  /**
   * Registers bundle information for the mock entity type.
   *
   * @param array $bundle_info
   *   The bundle information to register.
   */
  protected function registerBundleInfo($bundle_info) {
    $this->entityManager
      ->getBundleInfo($this->entityTypeId)
      ->willReturn([$this->entityTypeId => $bundle_info])
    ;
  }

}

abstract class RevisionableEntity extends Entity implements RevisionableInterface {}
