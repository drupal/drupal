<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Plugin\field\FieldPluginBaseTest.
 */

namespace Drupal\Tests\views\Unit\Plugin\field;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\Markup;
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
   * The mocked path renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $renderer;

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

    $this->renderer = $this->getMock('Drupal\Core\Render\RendererInterface');

    $container_builder = new ContainerBuilder();
    $container_builder->set('url_generator', $this->urlGenerator);
    $container_builder->set('path.validator', $this->pathValidator);
    $container_builder->set('unrouted_url_assembler', $this->unroutedUrlAssembler);
    $container_builder->set('request_stack', $this->requestStack);
    $container_builder->set('renderer', $this->renderer);
    \Drupal::setContainer($container_builder);
  }

  /**
   * Sets up the unrouted url assembler and the link generator.
   */
  protected function setUpUrlIntegrationServices() {
    $this->pathProcessor = $this->getMock('Drupal\Core\PathProcessor\OutboundPathProcessorInterface');
    $this->unroutedUrlAssembler = new UnroutedUrlAssembler($this->requestStack, $this->pathProcessor);

    \Drupal::getContainer()->set('unrouted_url_assembler', $this->unroutedUrlAssembler);

    $this->linkGenerator = new LinkGenerator($this->urlGenerator, $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface'), $this->renderer);
    $this->renderer
      ->method('render')
      ->willReturnCallback(
        // Pretend to do a render.
        function (&$elements, $is_root_call = FALSE) {
          // Mock the ability to theme links
          $link = $this->linkGenerator->generate($elements['#title'], $elements['#url']);
          if (isset($elements['#prefix'])) {
            $link = $elements['#prefix'] . $link;
          }
          if (isset($elements['#suffix'])) {
            $link = $link . $elements['#suffix'];
          }
          return Markup::create($link);
        }
      );
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
   * Test rendering with a more link.
   *
   * @param string $path
   *   An internal or external path.
   * @param string $url
   *   The final url used by the more link.
   *
   * @dataProvider providerTestRenderTrimmedWithMoreLinkAndPath
   * @covers ::renderText
   */
  public function testRenderTrimmedWithMoreLinkAndPath($path, $url) {
    $alter = [
      'trim' => TRUE,
      'max_length' => 7,
      'more_link' => TRUE,
      // Don't invoke translation.
      'ellipsis' => FALSE,
      'more_link_text' => 'more link',
      'more_link_path' => $path,
    ];

    $this->display->expects($this->any())
      ->method('getHandlers')
      ->willReturnMap([
        ['argument', []],
        ['field', []],
      ]);

    $this->setUpUrlIntegrationServices();
    $field = $this->setupTestField(['alter' => $alter]);
    $field->field_alias = 'key';
    $row = new ResultRow(['key' => 'a long value']);

    $expected_result = 'a long <a href="' . $url . '" class="views-more-link">more link</a>';
    $result = $field->advancedRender($row);
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Data provider for ::testRenderTrimmedWithMoreLinkAndPath().
   *
   * @return array
   *   Test data.
   */
  public function providerTestRenderTrimmedWithMoreLinkAndPath() {
    $data = [];
    // Simple path with default options.
    $data[] = ['test-path', '/test-path'];
    // Add a fragment.
    $data[] = ['test-path#test', '/test-path#test'];
    // Query specified as part of the path.
    $data[] = ['test-path?foo=bar', '/test-path?foo=bar'];
    // Empty path.
    $data[] = ['', '/%3Cfront%3E'];
    // Front page path.
    $data[] = ['<front>', '/%3Cfront%3E'];

    // External URL.
    $data[] = ['https://www.drupal.org', 'https://www.drupal.org'];
    $data[] = ['http://www.drupal.org', 'http://www.drupal.org'];
    $data[] = ['www.drupal.org', '/www.drupal.org'];

    return $data;
  }

  /**
   * Tests the "No results text" rendering.
   *
   * @covers ::renderText
   */
  public function testRenderNoResult() {
    $this->setupDisplayWithEmptyArgumentsAndFields();
    $field = $this->setupTestField(['empty' => 'This <strong>should work</strong>.']);
    $field->field_alias = 'key';
    $row = new ResultRow(['key' => '']);

    $expected_result = 'This <strong>should work</strong>.';
    $result = $field->advancedRender($row);
    $this->assertEquals($expected_result, $result);
    $this->assertInstanceOf('\Drupal\views\Render\ViewsRenderPipelineMarkup', $result);
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
    $this->assertEquals($final_html, (string) $result);
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
    $data[] = ['www.drupal.org', ['external' => TRUE], [], '<a href="http://www.drupal.org">value</a>'];
    $data[] = ['', ['external' => TRUE], [], 'value'];

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
      ->with($expected_url->getRouteName(), $expected_url->getRouteParameters(), $expected_url_options, TRUE)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl($url_path));

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
    $data[] = [$url, [], clone $url, '/test-path', clone $url, '<a href="/test-path">value</a>'];

    // Simple url with parameters.
    $url_parameters = Url::fromRoute('test_route', ['key' => 'value']);
    $data[] = [$url_parameters, [], clone $url_parameters, '/test-path/value', clone $url_parameters, '<a href="/test-path/value">value</a>'];

    // Add a fragment.
    $url = Url::fromRoute('test_route');
    $url_with_fragment = Url::fromRoute('test_route');
    $options = ['fragment' => 'test'] + $this->defaultUrlOptions;
    $url_with_fragment->setOptions($options);
    $data[] = [$url, ['fragment' => 'test'], $url_with_fragment, '/test-path#test', clone $url_with_fragment, '<a href="/test-path#test">value</a>'];

    // Rel attributes.
    $url = Url::fromRoute('test_route');
    $url_with_rel = Url::fromRoute('test_route');
    $options = ['attributes' => ['rel' => 'up']] + $this->defaultUrlOptions;
    $url_with_rel->setOptions($options);
    $data[] = [$url, ['rel' => 'up'], clone $url, '/test-path', $url_with_rel, '<a href="/test-path" rel="up">value</a>'];

    // Target attributes.
    $url = Url::fromRoute('test_route');
    $url_with_target = Url::fromRoute('test_route');
    $options = ['attributes' => ['target' => '_blank']] + $this->defaultUrlOptions;
    $url_with_target->setOptions($options);
    $data[] = [$url, ['target' => '_blank'], $url_with_target, '/test-path', clone $url_with_target, '<a href="/test-path" target="_blank">value</a>'];

    // Link attributes.
    $url = Url::fromRoute('test_route');
    $url_with_link_attributes = Url::fromRoute('test_route');
    $options = ['attributes' => ['foo' => 'bar']] + $this->defaultUrlOptions;
    $url_with_link_attributes->setOptions($options);
    $data[] = [$url, ['link_attributes' => ['foo' => 'bar']], clone $url, '/test-path', $url_with_link_attributes, '<a href="/test-path" foo="bar">value</a>'];

    // Manual specified query.
    $url = Url::fromRoute('test_route');
    $url_with_query = Url::fromRoute('test_route');
    $options = ['query' => ['foo' => 'bar']] + $this->defaultUrlOptions;
    $url_with_query->setOptions($options);
    $data[] = [$url, ['query' => ['foo' => 'bar']], clone $url_with_query, '/test-path?foo=bar', $url_with_query, '<a href="/test-path?foo=bar">value</a>'];

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
   * Test rendering of a link with a path and options.
   *
   * @dataProvider providerTestRenderAsLinkWithPathAndTokens
   * @covers ::renderAsLink
   */
  public function testRenderAsLinkWithPathAndTokens($path, $tokens, $link_html) {
    $alter = [
      'make_link' => TRUE,
      'path' => $path,
    ];

    $this->setUpUrlIntegrationServices();
    $this->setupDisplayWithEmptyArgumentsAndFields();
    $this->executable->build_info['substitutions'] = $tokens;
    $field = $this->setupTestField(['alter' => $alter]);
    $field->field_alias = 'key';
    $row = new ResultRow(['key' => 'value']);

    $build = [
      '#type' => 'inline_template',
      '#template' => 'test-path/' . explode('/', $path)[1],
      '#context' => ['foo' => 123],
      '#post_render' => [function() {}],
    ];

    $this->renderer->expects($this->once())
      ->method('renderPlain')
      ->with($build)
      ->willReturn('base:test-path/123');

    $result = $field->advancedRender($row);
    $this->assertEquals($link_html, $result);
  }

  /**
   * Data provider for ::testRenderAsLinkWithPathAndTokens().
   *
   * @return array
   *   Test data.
   */
  public function providerTestRenderAsLinkWithPathAndTokens() {
    $tokens = ['{{ foo }}' => 123];
    $link_html = '<a href="/test-path/123">value</a>';

    $data = [];

    $data[] = ['test-path/{{foo}}', $tokens, $link_html];
    $data[] = ['test-path/{{ foo}}', $tokens, $link_html];
    $data[] = ['test-path/{{  foo}}', $tokens, $link_html];
    $data[] = ['test-path/{{foo }}', $tokens, $link_html];
    $data[] = ['test-path/{{foo  }}', $tokens, $link_html];
    $data[] = ['test-path/{{ foo }}', $tokens, $link_html];
    $data[] = ['test-path/{{  foo }}', $tokens, $link_html];
    $data[] = ['test-path/{{ foo  }}', $tokens, $link_html];
    $data[] = ['test-path/{{  foo  }}', $tokens, $link_html];

    return $data;
  }

  /**
   * Test rendering of a link with a path and options.
   *
   * @dataProvider providerTestRenderAsExternalLinkWithPathAndTokens
   * @covers ::renderAsLink
   */
  public function testRenderAsExternalLinkWithPathAndTokens($path, $tokens, $link_html, $context) {
    $alter = [
      'make_link' => TRUE,
      'path' => $path,
      'url' => '',
    ];
    if (isset($context['alter'])) {
      $alter += $context['alter'];
    }

    $this->setUpUrlIntegrationServices();
    $this->setupDisplayWithEmptyArgumentsAndFields();
    $this->executable->build_info['substitutions'] = $tokens;
    $field = $this->setupTestField(['alter' => $alter]);
    $field->field_alias = 'key';
    $row = new ResultRow(['key' => 'value']);

    $build = [
      '#type' => 'inline_template',
      '#template' => $path,
      '#context' => ['foo' => $context['context_path']],
      '#post_render' => [function() {}],
    ];

    $this->renderer->expects($this->once())
      ->method('renderPlain')
      ->with($build)
      ->willReturn($context['context_path']);

    $result = $field->advancedRender($row);
    $this->assertEquals($link_html, $result);
  }

  /**
   * Data provider for ::testRenderAsExternalLinkWithPathAndTokens().
   *
   * @return array
   *   Test data.
   */
  public function providerTestRenderAsExternalLinkWithPathAndTokens() {
    $data = [];

    $data[] = ['{{ foo }}', ['{{ foo }}' => 'http://www.drupal.org'], '<a href="http://www.drupal.org">value</a>', ['context_path' => 'http://www.drupal.org']];
    $data[] = ['{{ foo }}', ['{{ foo }}' => ''], 'value', ['context_path' => '']];
    $data[] = ['{{ foo }}', ['{{ foo }}' => ''], 'value', ['context_path' => '', 'alter' => ['external' => TRUE]]];
    $data[] = ['{{ foo }}', ['{{ foo }}' => '/test-path/123'], '<a href="/test-path/123">value</a>', ['context_path' => '/test-path/123']];

    return $data;
  }

  /**
   * Sets up a test field.
   *
   * @return \Drupal\Tests\views\Unit\Plugin\field\FieldPluginBaseTestField|\PHPUnit_Framework_MockObject_MockObject
   *   The test field.
   */
  protected function setupTestField(array $options = []) {
    /** @var \Drupal\Tests\views\Unit\Plugin\field\FieldPluginBaseTestField $field */
    $field = $this->getMock('Drupal\Tests\views\Unit\Plugin\field\FieldPluginBaseTestField', ['l'], [$this->configuration, $this->pluginId, $this->pluginDefinition]);
    $field->init($this->executable, $this->display, $options);
    $field->setLinkGenerator($this->linkGenerator);

    return $field;
  }

  /**
   * @covers ::getRenderTokens
   */
  public function testGetRenderTokensWithoutFieldsAndArguments() {
    $field = $this->setupTestField();

    $this->display->expects($this->any())
      ->method('getHandlers')
      ->willReturnMap([
        ['argument', []],
        ['field', []],
      ]);

    $this->assertEquals([], $field->getRenderTokens([]));
  }

  /**
   * @covers ::getRenderTokens
   */
  public function testGetRenderTokensWithoutArguments() {
    $field = $this->setupTestField(['id' => 'id']);

    $field->last_render = 'last rendered output';
    $this->display->expects($this->any())
      ->method('getHandlers')
      ->willReturnMap([
        ['argument', []],
        ['field', ['id' => $field]],
      ]);

    $this->assertEquals(['{{ id }}' => 'last rendered output'], $field->getRenderTokens([]));
  }

  /**
   * @covers ::getRenderTokens
   */
  public function testGetRenderTokensWithArguments() {
    $field = $this->setupTestField(['id' => 'id']);
    $field->view->args = ['argument value'];
    $field->view->build_info['substitutions']['{{ arguments.name }}'] = 'argument value';

    $argument = $this->getMockBuilder('\Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $field->last_render = 'last rendered output';
    $this->display->expects($this->any())
      ->method('getHandlers')
      ->willReturnMap([
        ['argument', ['name' => $argument]],
        ['field', ['id' => $field]],
      ]);

    $expected = [
      '{{ id }}' => 'last rendered output',
      '{{ arguments.name }}' => 'argument value',
      '{{ raw_arguments.name }}' => 'argument value',
    ];
    $this->assertEquals($expected, $field->getRenderTokens([]));
  }

}

class FieldPluginBaseTestField extends FieldPluginBase {

  public function setLinkGenerator(LinkGeneratorInterface $link_generator) {
    $this->linkGenerator = $link_generator;
  }

}

// @todo Remove as part of https://www.drupal.org/node/2529170.
namespace Drupal\views\Plugin\views\field;

if (!function_exists('base_path')) {
  function base_path() {
    return '/';
  }
}
