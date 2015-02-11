<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Plugin\field\FieldPluginBaseTest.
 */

namespace Drupal\Tests\views\Unit\Plugin\field;

use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Utility\UnroutedUrlAssembler;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\field\FieldPluginBase
 * @group views
 */
class FieldPluginBaseTest extends UnitTestCase {

  /**
   * The configuration of the plugin under test.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The ID plugin of the plugin under test.
   *
   * @var string
   */
  protected $pluginId = 'field_test';

  /**
   * The definition of the plugin under test.
   *
   * @var array
   */
  protected $pluginDefinition = [];

  /**
   * Default configuration for URL output.
   *
   * @var array
   */
  protected $defaultUrlOptions = [
    'absolute' => FALSE,
    'alias' => FALSE,
    'entity' => NULL,
    'entity_type' => NULL,
    'html' => TRUE,
    'language' => NULL,
    'query' => [],
    'set_active_class' => FALSE,
  ];

  /**
   * The mocked link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $linkGenerator;

  /**
   * The mocked view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executable;

  /**
   * The mocked display plugin instance.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $display;

  /**
   * The mocked url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathValidator;

  /**
   * The unrouted url assembler service.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $unroutedUrlAssembler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The mocked path processor.
   *
   * @var \Drupal\Core\PathProcessor\OutboundPathProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathProcessor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $route_provider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $route_provider->expects($this->any())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn(new Route('/test-path'));

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->pathValidator = $this->getMock('Drupal\Core\Path\PathValidatorInterface');

    $this->requestStack = new RequestStack();
    $this->requestStack->push(new Request());

    $this->unroutedUrlAssembler = $this->getMock('Drupal\Core\Utility\UnroutedUrlAssemblerInterface');
    $this->linkGenerator = $this->getMock('Drupal\Core\Utility\LinkGeneratorInterface');

    $container_builder = new ContainerBuilder();
    $container_builder->set('url_generator', $this->urlGenerator);
    $container_builder->set('path.validator', $this->pathValidator);
    $container_builder->set('unrouted_url_assembler', $this->unroutedUrlAssembler);
    $container_builder->set('request_stack', $this->requestStack);
    \Drupal::setContainer($container_builder);
  }

  /**
   * Sets up the unrouted url assembler and the link generator.
   */
  protected function setUpUrlIntegrationServices() {
    $config = $this->getMockBuilder('Drupal\Core\Config\ImmutableConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $config_factory = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->any())
      ->method('get')
      ->willReturn($config);

    $this->pathProcessor = $this->getMock('Drupal\Core\PathProcessor\OutboundPathProcessorInterface');
    $this->unroutedUrlAssembler = new UnroutedUrlAssembler($this->requestStack, $config_factory, $this->pathProcessor);

    \Drupal::getContainer()->set('unrouted_url_assembler', $this->unroutedUrlAssembler);

    $this->linkGenerator = new LinkGenerator($this->urlGenerator, $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface'));
  }

  /**
   * Sets up a display with empty arguments and fields.
   */
  protected function setupDisplayWithEmptyArgumentsAndFields() {
    $this->display->expects($this->any())
      ->method('getHandlers')
      ->willReturnMap([
        ['argument', []],
        ['field', []],
      ]);
  }

  /**
   * Test rendering as a link without a path.
   *
   * @covers ::renderAsLink
   */
  public function testRenderAsLinkWithoutPath() {
    $alter = [
      'make_link' => TRUE,
    ];

    $this->setUpUrlIntegrationServices();
    $field = $this->setupTestField(['alter' => $alter]);
    $field->field_alias = 'key';
    $row = new ResultRow(['key' => 'value']);

    $expected_result = 'value';
    $result = $field->advancedRender($row);
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Test rendering of a link with a path and options.
   *
   * @dataProvider providerTestRenderAsLinkWithPathAndOptions
   * @covers ::renderAsLink
   */
  public function testRenderAsLinkWithPathAndOptions($path, $alter, $link_html, $final_html = NULL) {
    $alter += [
      'make_link' => TRUE,
      'path' => $path,
    ];

    $final_html = isset($final_html) ? $final_html : $link_html;

    $this->setUpUrlIntegrationServices();
    $this->setupDisplayWithEmptyArgumentsAndFields();
    $field = $this->setupTestField(['alter' => $alter]);
    $field->field_alias = 'key';
    $row = new ResultRow(['key' => 'value']);

    $result = $field->advancedRender($row);
    $this->assertEquals($final_html, $result);
  }

  /**
   * Data provider for ::testRenderAsLinkWithPathAndOptions().
   *
   * @return array
   *   Test data.
   */
  public function providerTestRenderAsLinkWithPathAndOptions() {
    $data = [];
    // Simple path with default options.
    $data[] = ['test-path', [], [], '<a href="/test-path">value</a>'];
    // Add a fragment.
    $data[] = ['test-path', ['fragment' => 'test'], '<a href="/test-path#test">value</a>'];
    // Rel attributes.
    $data[] = ['test-path', ['rel' => 'up'], '<a href="/test-path" rel="up">value</a>'];
    // Target attributes.
    $data[] = ['test-path', ['target' => '_blank'], '<a href="/test-path" target="_blank">value</a>'];
    // Link attributes.
    $data[] = ['test-path', ['link_attributes' => ['foo' => 'bar']], '<a href="/test-path" foo="bar">value</a>'];
    // Manual specified query.
    $data[] = ['test-path', ['query' => ['foo' => 'bar']], '<a href="/test-path?foo=bar">value</a>'];
    // Query specified as part of the path.
    $data[] = ['test-path?foo=bar', [], '<a href="/test-path?foo=bar">value</a>'];
    // Query specified as option and path.
    // @todo Do we expect that options override all existing ones?
    $data[] = ['test-path?foo=bar', ['query' => ['key' => 'value']], '<a href="/test-path?key=value">value</a>'];
    // Alias flag.
    $data[] = ['test-path', ['alias' => TRUE], '<a href="/test-path">value</a>'];
    // Note: In contrast to the testRenderAsLinkWithUrlAndOptions test we don't
    // test the language, because the path processor for the language won't be
    // executed for paths which aren't routed.

    // Entity flag.
    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $data[] = ['test-path', ['entity' => $entity], '<a href="/test-path">value</a>'];
    // entity_type flag.
    $entity_type_id = 'node';
    $data[] = ['test-path', ['entity_type' => $entity_type_id], '<a href="/test-path">value</a>'];
    // prefix
    $data[] = ['test-path', ['prefix' => 'test_prefix'], '<a href="/test-path">value</a>', 'test_prefix<a href="/test-path">value</a>'];
    // suffix.
    $data[] = ['test-path', ['suffix' => 'test_suffix'], '<a href="/test-path">value</a>', '<a href="/test-path">value</a>test_suffix'];

    // External URL.
    $data[] = ['https://www.drupal.org', [], [], '<a href="https://www.drupal.org">value</a>'];

    return $data;
  }

  /**
   * Tests link rendering with a URL and options.
   *
   * @dataProvider providerTestRenderAsLinkWithUrlAndOptions
   * @covers ::renderAsLink
   */
  public function testRenderAsLinkWithUrlAndOptions(Url $url, $alter, Url $expected_url, $url_path, Url $expected_link_url, $link_html, $final_html = NULL) {
    $alter += [
      'make_link' => TRUE,
      'url' => $url,
    ];

    $final_html = isset($final_html) ? $final_html : $link_html;

    $this->setUpUrlIntegrationServices();
    $this->setupDisplayWithEmptyArgumentsAndFields();
    $field = $this->setupTestField(['alter' => $alter]);
    $field->field_alias = 'key';
    $row = new ResultRow(['key' => 'value']);

    $expected_url->setOptions($expected_url->getOptions() + $this->defaultUrlOptions);
    $expected_link_url->setUrlGenerator($this->urlGenerator);

    $expected_url_options = $expected_url->getOptions();
    unset($expected_url_options['attributes']);

    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with($expected_url->getRouteName(), $expected_url->getRouteParameters(), $expected_url_options)
      ->willReturn($url_path);

    $result = $field->advancedRender($row);
    $this->assertEquals($final_html, $result);
  }

  /**
   * Data provider for ::testRenderAsLinkWithUrlAndOptions().
   *
   * @return array
   *   Array of test data.
   */
  public function providerTestRenderAsLinkWithUrlAndOptions() {
    $data = [];

    // Simple path with default options.
    $url = Url::fromRoute('test_route');
    $data[]= [$url, [], clone $url, '/test-path', clone $url, '<a href="/test-path">value</a>'];

    // Simple url with parameters.
    $url_parameters = Url::fromRoute('test_route', ['key' => 'value']);
    $data[]= [$url_parameters, [], clone $url_parameters, '/test-path/value', clone $url_parameters, '<a href="/test-path/value">value</a>'];

    // Add a fragment.
    $url = Url::fromRoute('test_route');
    $url_with_fragment = Url::fromRoute('test_route');
    $options = ['fragment' => 'test'] + $this->defaultUrlOptions;
    $url_with_fragment->setOptions($options);
    $data[]= [$url, ['fragment' => 'test'], $url_with_fragment, '/test-path#test', clone $url_with_fragment, '<a href="/test-path#test">value</a>'];

    // Rel attributes.
    $url = Url::fromRoute('test_route');
    $url_with_rel = Url::fromRoute('test_route');
    $options = ['attributes' => ['rel' => 'up']] + $this->defaultUrlOptions;
    $url_with_rel->setOptions($options);
    $data[]= [$url, ['rel' => 'up'], clone $url, '/test-path', $url_with_rel, '<a href="/test-path" rel="up">value</a>'];

    // Target attributes.
    $url = Url::fromRoute('test_route');
    $url_with_target = Url::fromRoute('test_route');
    $options = ['attributes' => ['target' => '_blank']] + $this->defaultUrlOptions;
    $url_with_target->setOptions($options);
    $data[]= [$url, ['target' => '_blank'], $url_with_target, '/test-path', clone $url_with_target, '<a href="/test-path" target="_blank">value</a>'];

    // Link attributes.
    $url = Url::fromRoute('test_route');
    $url_with_link_attributes = Url::fromRoute('test_route');
    $options = ['attributes' => ['foo' => 'bar']] + $this->defaultUrlOptions;
    $url_with_link_attributes->setOptions($options);
    $data[]= [$url, ['link_attributes' => ['foo' => 'bar']], clone $url, '/test-path', $url_with_link_attributes, '<a href="/test-path" foo="bar">value</a>'];

    // Manual specified query.
    $url = Url::fromRoute('test_route');
    $url_with_query = Url::fromRoute('test_route');
    $options = ['query' => ['foo' => 'bar']] + $this->defaultUrlOptions;
    $url_with_query->setOptions($options);
    $data[]= [$url, ['query' => ['foo' => 'bar']], clone $url_with_query, '/test-path?foo=bar', $url_with_query, '<a href="/test-path?foo=bar">value</a>'];

    // Query specified as part of the path.
    $url = Url::fromRoute('test_route')->setOption('query', ['foo' => 'bar']);
    $url_with_query = clone $url;
    $url_with_query->setOptions(['query' => ['foo' => 'bar']] + $url_with_query->getOptions());
    $data[] = [$url, [], $url_with_query, '/test-path?foo=bar', clone $url, '<a href="/test-path?foo=bar">value</a>'];

    // Query specified as option and path.
    $url = Url::fromRoute('test_route')->setOption('query', ['foo' => 'bar']);
    $url_with_query = Url::fromRoute('test_route');
    $options = ['query' => ['key' => 'value']] + $this->defaultUrlOptions;
    $url_with_query->setOptions($options);
    $data[] = [$url, ['query' => ['key' => 'value']], $url_with_query, '/test-path?key=value', clone $url_with_query, '<a href="/test-path?key=value">value</a>'];

    // Alias flag.
    $url = Url::fromRoute('test_route');
    $url_without_alias = Url::fromRoute('test_route');
    $options = ['alias' => TRUE] + $this->defaultUrlOptions;
    $url_without_alias->setOptions($options);
    $data[] = [$url, ['alias' => TRUE], $url_without_alias, '/test-path', clone $url_without_alias, '<a href="/test-path">value</a>'];

    // Language flag.
    $language = new Language(['id' => 'fr']);
    $url = Url::fromRoute('test_route');
    $url_with_language = Url::fromRoute('test_route');
    $options = ['language' => $language] + $this->defaultUrlOptions;
    $url_with_language->setOptions($options);
    $data[] = [$url, ['language' => $language], $url_with_language, '/fr/test-path', clone $url_with_language, '<a href="/fr/test-path" hreflang="fr">value</a>'];

    // Entity flag.
    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $url = Url::fromRoute('test_route');
    $url_with_entity = Url::fromRoute('test_route');
    $options = ['entity' => $entity] + $this->defaultUrlOptions;
    $url_with_entity->setOptions($options);
    $data[] = [$url, ['entity' => $entity], $url_with_entity, '/test-path', clone $url_with_entity, '<a href="/test-path">value</a>'];

    // Test entity_type flag.
    $entity_type_id = 'node';
    $url = Url::fromRoute('test_route');
    $url_with_entity_type = Url::fromRoute('test_route');
    $options = ['entity_type' => $entity_type_id] + $this->defaultUrlOptions;
    $url_with_entity_type->setOptions($options);
    $data[] = [$url, ['entity_type' => $entity_type_id], $url_with_entity_type, '/test-path', clone $url_with_entity_type, '<a href="/test-path">value</a>'];

    // Test prefix.
    $url = Url::fromRoute('test_route');
    $data[] = [$url, ['prefix' => 'test_prefix'], clone $url, '/test-path', clone $url, '<a href="/test-path">value</a>', 'test_prefix<a href="/test-path">value</a>'];

    // Test suffix.
    $url = Url::fromRoute('test_route');
    $data[] = [$url, ['suffix' => 'test_suffix'], clone $url, '/test-path', clone $url, '<a href="/test-path">value</a>', '<a href="/test-path">value</a>test_suffix'];

    return $data;
  }

  /**
   * Sets up a test field.
   *
   * @return \Drupal\Tests\views\Unit\Plugin\field\TestField|\PHPUnit_Framework_MockObject_MockObject
   *   The test field.
   */
  protected function setupTestField(array $options = []) {
    /** @var \Drupal\Tests\views\Unit\Plugin\field\TestField $field */
    $field = $this->getMock('Drupal\Tests\views\Unit\Plugin\field\TestField', ['l'], [$this->configuration, $this->pluginId, $this->pluginDefinition]);
    $field->init($this->executable, $this->display, $options);
    $field->setLinkGenerator($this->linkGenerator);

    return $field;
  }

}

class TestField extends FieldPluginBase {

  public function setLinkGenerator(LinkGeneratorInterface $link_generator) {
    $this->linkGenerator = $link_generator;
  }

}
