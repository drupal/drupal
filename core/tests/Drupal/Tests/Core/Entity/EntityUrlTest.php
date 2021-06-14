<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Tests URL handling of the \Drupal\Core\Entity\EntityBase class.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityBase
 *
 * @group Entity
 */
class EntityUrlTest extends UnitTestCase {

  /**
   * The entity type bundle info service mock used in this test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

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
   * Indicator that the test entity type has no bundle key.
   *
   * @var false
   */
  const HAS_NO_BUNDLE_KEY = FALSE;

  /**
   * Indicator that the test entity type has a bundle key.
   *
   * @var true
   */
  const HAS_BUNDLE_KEY = TRUE;

  /**
   * Tests the toUrl() method without an entity ID.
   *
   * @covers ::toUrl
   */
  public function testToUrlNoId() {
    $entity = $this->getEntity(EntityBase::class, []);

    $this->expectException(EntityMalformedException::class);
    $this->expectExceptionMessage('The "' . $this->entityTypeId . '" entity cannot have a URI as it does not have an ID');
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
    $entity = $this->getEntity(EntityBase::class, $values);
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
    // template registered with the entity, we ask for the 'revision' link
    // template instead of 'canonical', to test that it falls back to the
    // 'canonical' link template in case of the default revision.
    $link_template = $link_template === 'canonical' ? 'revision' : $link_template;
    /** @var \Drupal\Core\Url $url */
    $url = $entity->toUrl($link_template);
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
    $test_cases['revision-delete'] = [static::NON_DEFAULT_REVISION, 'revision-delete-form', 'entity.test_entity.revision_delete_form', $route_parameters];

    return $test_cases;
  }

  /**
   * Tests the toUrl() method with link templates without an entity ID.
   *
   * @param string $link_template
   *   The link template to test.
   * @param string $expected_route_name
   *   The expected route name of the generated URL.
   *
   * @dataProvider providerTestToUrlLinkTemplateNoId
   *
   * @covers ::toUrl
   * @covers ::linkTemplates
   * @covers ::urlRouteParameters
   */
  public function testToUrlLinkTemplateNoId($link_template, $expected_route_name) {
    $entity = $this->getEntity(EntityBase::class, ['id' => $this->entityId]);
    $this->registerLinkTemplate($link_template);

    /** @var \Drupal\Core\Url $url */
    $url = $entity->toUrl($link_template);
    $this->assertUrl($expected_route_name, [], $entity, FALSE, $url);
  }

  /**
   * Provides data for testToUrlLinkTemplateNoId().
   *
   * @return array
   *   An array of test cases for testToUrlLinkTemplateNoId().
   */
  public function providerTestToUrlLinkTemplateNoId() {
    $test_cases = [];

    $test_cases['collection'] = ['collection', 'entity.test_entity.collection'];
    $test_cases['add-page'] = ['add-page', 'entity.test_entity.add_page'];

    return $test_cases;
  }

  /**
   * Tests the toUrl() method with the 'revision' link template.
   *
   * @param bool $has_bundle_key
   *   Whether or not the mock entity type should have a bundle key.
   * @param string|null $bundle_entity_type
   *   The ID of the bundle entity type of the mock entity type, or NULL if the
   *   mock entity type should not have a bundle entity type.
   * @param string $bundle_key
   *   The bundle key of the mock entity type or FALSE if the entity type should
   *   not have a bundle key.
   * @param array $expected_route_parameters
   *   The expected route parameters of the generated URL.
   *
   * @dataProvider providerTestToUrlLinkTemplateAddForm
   *
   * @covers ::toUrl
   * @covers ::linkTemplates
   * @covers ::urlRouteParameters
   */
  public function testToUrlLinkTemplateAddForm($has_bundle_key, $bundle_entity_type, $bundle_key, $expected_route_parameters) {
    $values = ['id' => $this->entityId, 'langcode' => $this->langcode];
    $entity = $this->getEntity(EntityBase::class, $values);
    $this->entityType->hasKey('bundle')->willReturn($has_bundle_key);
    $this->entityType->getBundleEntityType()->willReturn($bundle_entity_type);
    $this->entityType->getKey('bundle')->willReturn($bundle_key);
    $link_template = 'add-form';
    $this->registerLinkTemplate($link_template);

    /** @var \Drupal\Core\Url $url */
    $url = $entity->toUrl($link_template);
    $this->assertUrl('entity.test_entity.add_form', $expected_route_parameters, $entity, FALSE, $url);
  }

  /**
   * Provides data for testToUrlLinkTemplateAddForm().
   *
   * @return array
   *   An array of test cases for testToUrlLinkTemplateAddForm().
   */
  public function providerTestToUrlLinkTemplateAddForm() {
    $test_cases = [];

    $route_parameters = [];
    $test_cases['no_bundle_key'] = [static::HAS_NO_BUNDLE_KEY, NULL, FALSE, $route_parameters];

    $route_parameters = ['type' => $this->entityTypeId];
    $test_cases['bundle_entity_type'] = [static::HAS_BUNDLE_KEY, 'type', FALSE, $route_parameters];
    $test_cases['bundle_key'] = [static::HAS_BUNDLE_KEY, NULL, 'type', $route_parameters];

    return $test_cases;
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
    $entity = $this->getEntity(EntityBase::class, ['id' => $this->entityId]);

    $this->registerBundleInfo($bundle_info);
    $this->entityType->getUriCallback()->willReturn($uri_callback);

    $link_template = 'canonical';
    $this->expectException(UndefinedLinkTemplateException::class);
    $this->expectExceptionMessage("No link template '$link_template' found for the '$this->entityTypeId' entity type");
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
    $entity = $this->getEntity(EntityBase::class, ['id' => $this->entityId, 'langcode' => $this->langcode]);

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

    $uri_callback = function () {
      return Url::fromRoute('<none>');
    };
    $test_cases['uri_callback'] = [[], $uri_callback];
    $test_cases['bundle_uri_callback'] = [['uri_callback' => $uri_callback], NULL];

    return $test_cases;
  }

  /**
   * Tests the uriRelationships() method.
   *
   * @covers ::uriRelationships
   */
  public function testUriRelationships() {
    $entity = $this->getEntity(EntityBase::class, ['id' => $this->entityId]);

    $container_builder = new ContainerBuilder();
    $url_generator = $this->createMock(UrlGeneratorInterface::class);
    $container_builder->set('url_generator', $url_generator);
    \Drupal::setContainer($container_builder);

    // Test route with no mandatory parameters.
    $this->registerLinkTemplate('canonical');
    $route_name_0 = 'entity.' . $this->entityTypeId . '.canonical';
    $url_generator->expects($this->at(0))
      ->method('generateFromRoute')
      ->with($route_name_0)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/entity_test'));
    $this->assertEquals(['canonical'], $entity->uriRelationships());

    // Test route with non-default mandatory parameters.
    $this->registerLinkTemplate('{non_default_parameter}');
    $route_name_1 = 'entity.' . $this->entityTypeId . '.{non_default_parameter}';
    $url_generator->expects($this->at(0))
      ->method('generateFromRoute')
      ->with($route_name_1)
      ->willThrowException(new MissingMandatoryParametersException());
    $this->assertEquals([], $entity->uriRelationships());
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
   *   The getEntityType() and entityTypeBundleInfo() methods are always mocked.
   *
   * @return \Drupal\Core\Entity\Entity|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getEntity($class, array $values, array $methods = []) {
    $methods = array_merge($methods, ['getEntityType', 'entityTypeBundleInfo']);

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

    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $entity->method('entityTypeBundleInfo')->willReturn($this->entityTypeBundleInfo->reveal());

    return $entity;
  }

  /**
   * Asserts that a given URL object matches the expectations.
   *
   * @param string $expected_route_name
   *   The expected route name of the generated URL.
   * @param array $expected_route_parameters
   *   The expected route parameters of the generated URL.
   * @param \Drupal\Core\Entity\Entity|\PHPUnit\Framework\MockObject\MockObject $entity
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
    $this->entityTypeBundleInfo
      ->getBundleInfo($this->entityTypeId)
      ->willReturn([$this->entityTypeId => $bundle_info]);
  }

}

abstract class RevisionableEntity extends EntityBase implements RevisionableInterface {}
