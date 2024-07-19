<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\field;

use Drupal\Core\Entity\EntityInterface;
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
use Prophecy\Prophet;
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
   */
  protected const DEFAULT_URL_OPTIONS = [
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
   * @var \Drupal\Core\Utility\LinkGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $linkGenerator;

  /**
   * The mocked view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executable;

  /**
   * The mocked display plugin instance.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $display;

  /**
   * The mocked URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pathValidator;

  /**
   * The unrouted URL assembler service.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface|\PHPUnit\Framework\MockObject\MockObject
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
   * @var \Drupal\Core\PathProcessor\OutboundPathProcessorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pathProcessor;

  /**
   * The mocked path renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executable->style_plugin = $this->getMockBuilder('Drupal\views\Plugin\views\style\StylePluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $route_provider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $route_provider->expects($this->any())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn(new Route('/test-path'));

    $this->urlGenerator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->pathValidator = $this->createMock('Drupal\Core\Path\PathValidatorInterface');

    $this->requestStack = new RequestStack();
    $this->requestStack->push(new Request());

    $this->unroutedUrlAssembler = $this->createMock('Drupal\Core\Utility\UnroutedUrlAssemblerInterface');
    $this->linkGenerator = $this->createMock('Drupal\Core\Utility\LinkGeneratorInterface');

    $this->renderer = $this->createMock('Drupal\Core\Render\RendererInterface');

    $container_builder = new ContainerBuilder();
    $container_builder->set('url_generator', $this->urlGenerator);
    $container_builder->set('path.validator', $this->pathValidator);
    $container_builder->set('unrouted_url_assembler', $this->unroutedUrlAssembler);
    $container_builder->set('request_stack', $this->requestStack);
    $container_builder->set('renderer', $this->renderer);
    \Drupal::setContainer($container_builder);
  }

  /**
   * Sets up the unrouted URL assembler and the link generator.
   */
  protected function setUpUrlIntegrationServices() {
    $this->pathProcessor = $this->createMock('Drupal\Core\PathProcessor\OutboundPathProcessorInterface');
    $this->unroutedUrlAssembler = new UnroutedUrlAssembler($this->requestStack, $this->pathProcessor);

    \Drupal::getContainer()->set('unrouted_url_assembler', $this->unroutedUrlAssembler);

    $this->linkGenerator = new LinkGenerator($this->urlGenerator, $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface'), $this->renderer);
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
   * Tests rendering as a link without a path.
   *
   * @covers ::renderAsLink
   */
  public function testRenderAsLinkWithoutPath(): void {
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
   * Tests rendering with a more link.
   *
   * @param string $path
   *   An internal or external path.
   * @param string $url
   *   The final URL used by the more link.
   *
   * @dataProvider providerTestRenderTrimmedWithMoreLinkAndPath
   * @covers ::renderText
   */
  public function testRenderTrimmedWithMoreLinkAndPath($path, $url): void {
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
  public static function providerTestRenderTrimmedWithMoreLinkAndPath() {
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
    $data[] = ['https://www.example.com', 'https://www.example.com'];
    $data[] = ['http://www.example.com', 'http://www.example.com'];
    $data[] = ['www.example.com', '/www.example.com'];

    return $data;
  }

  /**
   * Tests the "No results text" rendering.
   *
   * @covers ::renderText
   */
  public function testRenderNoResult(): void {
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
   * Tests rendering of a link with a path and options.
   *
   * @dataProvider providerTestRenderAsLinkWithPathAndOptions
   * @covers ::renderAsLink
   */
  public function testRenderAsLinkWithPathAndOptions($path, $alter, $final_html): void {
    $alter += [
      'make_link' => TRUE,
      'path' => $path,
    ];

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
  public static function providerTestRenderAsLinkWithPathAndOptions() {
    $data = [];
    // Simple path with default options.
    $data[] = ['test-path', [], '<a href="/test-path">value</a>'];
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
    $data[] = ['test-path', ['entity' => new \stdClass()], '<a href="/test-path">value</a>'];
    // entity_type flag.
    $entity_type_id = 'node';
    $data[] = ['test-path', ['entity_type' => $entity_type_id], '<a href="/test-path">value</a>'];
    // Prefix
    $data[] = ['test-path', ['prefix' => 'test_prefix'], 'test_prefix<a href="/test-path">value</a>'];
    // suffix.
    $data[] = ['test-path', ['suffix' => 'test_suffix'], '<a href="/test-path">value</a>test_suffix'];

    // External URL.
    $data[] = ['https://www.example.com', [], '<a href="https://www.example.com">value</a>'];
    $data[] = ['www.example.com', ['external' => TRUE], '<a href="http://www.example.com">value</a>'];
    $data[] = ['', ['external' => TRUE], 'value'];

    return $data;
  }

  /**
   * Tests link rendering with a URL and options.
   *
   * @dataProvider providerTestRenderAsLinkWithUrlAndOptions
   * @covers ::renderAsLink
   */
  public function testRenderAsLinkWithUrlAndOptions(Url $url, $alter, Url $expected_url, $url_path, Url $expected_link_url, $final_html): void {
    $alter += [
      'make_link' => TRUE,
      'url' => $url,
    ];

    $this->setUpUrlIntegrationServices();
    $this->setupDisplayWithEmptyArgumentsAndFields();
    $field = $this->setupTestField(['alter' => $alter]);
    $field->field_alias = 'key';
    $row = new ResultRow(['key' => 'value']);

    $expected_url->setOptions($expected_url->getOptions() + static::DEFAULT_URL_OPTIONS);
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
  public static function providerTestRenderAsLinkWithUrlAndOptions() {
    $data = [];

    // Simple path with default options.
    $url = Url::fromRoute('test_route');
    $data[] = [$url, [], clone $url, '/test-path', clone $url, '<a href="/test-path">value</a>'];

    // Simple URL with parameters.
    $url_parameters = Url::fromRoute('test_route', ['key' => 'value']);
    $data[] = [$url_parameters, [], clone $url_parameters, '/test-path/value', clone $url_parameters, '<a href="/test-path/value">value</a>'];

    // Add a fragment.
    $url = Url::fromRoute('test_route');
    $url_with_fragment = Url::fromRoute('test_route');
    $options = ['fragment' => 'test'] + static::DEFAULT_URL_OPTIONS;
    $url_with_fragment->setOptions($options);
    $data[] = [$url, ['fragment' => 'test'], $url_with_fragment, '/test-path#test', clone $url_with_fragment, '<a href="/test-path#test">value</a>'];

    // Rel attributes.
    $url = Url::fromRoute('test_route');
    $url_with_rel = Url::fromRoute('test_route');
    $options = ['attributes' => ['rel' => 'up']] + static::DEFAULT_URL_OPTIONS;
    $url_with_rel->setOptions($options);
    $data[] = [$url, ['rel' => 'up'], clone $url, '/test-path', $url_with_rel, '<a href="/test-path" rel="up">value</a>'];

    // Target attributes.
    $url = Url::fromRoute('test_route');
    $url_with_target = Url::fromRoute('test_route');
    $options = ['attributes' => ['target' => '_blank']] + static::DEFAULT_URL_OPTIONS;
    $url_with_target->setOptions($options);
    $data[] = [$url, ['target' => '_blank'], $url_with_target, '/test-path', clone $url_with_target, '<a href="/test-path" target="_blank">value</a>'];

    // Link attributes.
    $url = Url::fromRoute('test_route');
    $url_with_link_attributes = Url::fromRoute('test_route');
    $options = ['attributes' => ['foo' => 'bar']] + static::DEFAULT_URL_OPTIONS;
    $url_with_link_attributes->setOptions($options);
    $data[] = [$url, ['link_attributes' => ['foo' => 'bar']], clone $url, '/test-path', $url_with_link_attributes, '<a href="/test-path" foo="bar">value</a>'];

    // Manual specified query.
    $url = Url::fromRoute('test_route');
    $url_with_query = Url::fromRoute('test_route');
    $options = ['query' => ['foo' => 'bar']] + static::DEFAULT_URL_OPTIONS;
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
    $options = ['query' => ['key' => 'value']] + static::DEFAULT_URL_OPTIONS;
    $url_with_query->setOptions($options);
    $data[] = [$url, ['query' => ['key' => 'value']], $url_with_query, '/test-path?key=value', clone $url_with_query, '<a href="/test-path?key=value">value</a>'];

    // Alias flag.
    $url = Url::fromRoute('test_route');
    $url_without_alias = Url::fromRoute('test_route');
    $options = ['alias' => TRUE] + static::DEFAULT_URL_OPTIONS;
    $url_without_alias->setOptions($options);
    $data[] = [$url, ['alias' => TRUE], $url_without_alias, '/test-path', clone $url_without_alias, '<a href="/test-path">value</a>'];

    // Language flag.
    $language = new Language(['id' => 'fr']);
    $url = Url::fromRoute('test_route');
    $url_with_language = Url::fromRoute('test_route');
    $options = ['language' => $language] + static::DEFAULT_URL_OPTIONS;
    $url_with_language->setOptions($options);
    $data[] = [$url, ['language' => $language], $url_with_language, '/fr/test-path', clone $url_with_language, '<a href="/fr/test-path" hreflang="fr">value</a>'];

    // Entity flag.
    $entity = (new Prophet())->prophesize(EntityInterface::class)->reveal();
    $url = Url::fromRoute('test_route');
    $url_with_entity = Url::fromRoute('test_route');
    $options = ['entity' => $entity] + static::DEFAULT_URL_OPTIONS;
    $url_with_entity->setOptions($options);
    $data[] = [$url, ['entity' => $entity], $url_with_entity, '/test-path', clone $url_with_entity, '<a href="/test-path">value</a>'];

    // Test entity_type flag.
    $entity_type_id = 'node';
    $url = Url::fromRoute('test_route');
    $url_with_entity_type = Url::fromRoute('test_route');
    $options = ['entity_type' => $entity_type_id] + static::DEFAULT_URL_OPTIONS;
    $url_with_entity_type->setOptions($options);
    $data[] = [$url, ['entity_type' => $entity_type_id], $url_with_entity_type, '/test-path', clone $url_with_entity_type, '<a href="/test-path">value</a>'];

    // Test prefix.
    $url = Url::fromRoute('test_route');
    $data[] = [$url, ['prefix' => 'test_prefix'], clone $url, '/test-path', clone $url, 'test_prefix<a href="/test-path">value</a>'];

    // Test suffix.
    $url = Url::fromRoute('test_route');
    $data[] = [$url, ['suffix' => 'test_suffix'], clone $url, '/test-path', clone $url, '<a href="/test-path">value</a>test_suffix'];

    return $data;
  }

  /**
   * Tests rendering of a link with a path and options.
   *
   * @dataProvider providerTestRenderAsLinkWithPathAndTokens
   * @covers ::renderAsLink
   */
  public function testRenderAsLinkWithPathAndTokens($path, $tokens, $link_html): void {
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
      '#post_render' => [function () {}],
    ];

    $this->renderer->expects($this->once())
      ->method('renderInIsolation')
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
  public static function providerTestRenderAsLinkWithPathAndTokens() {
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
   * Tests rendering of a link with a path and options.
   *
   * @dataProvider providerTestRenderAsExternalLinkWithPathAndTokens
   * @covers ::renderAsLink
   */
  public function testRenderAsExternalLinkWithPathAndTokens($path, $tokens, $link_html, $context): void {
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
      '#post_render' => [function () {}],
    ];

    $this->renderer->expects($this->once())
      ->method('renderInIsolation')
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
  public static function providerTestRenderAsExternalLinkWithPathAndTokens() {
    $data = [];

    $data[] = ['{{ foo }}', ['{{ foo }}' => 'http://www.example.com'], '<a href="http://www.example.com">value</a>', ['context_path' => 'http://www.example.com']];
    $data[] = ['{{ foo }}', ['{{ foo }}' => ''], 'value', ['context_path' => '']];
    $data[] = ['{{ foo }}', ['{{ foo }}' => ''], 'value', ['context_path' => '', 'alter' => ['external' => TRUE]]];
    $data[] = ['{{ foo }}', ['{{ foo }}' => '/test-path/123'], '<a href="/test-path/123">value</a>', ['context_path' => '/test-path/123']];

    return $data;
  }

  /**
   * Sets up a test field.
   *
   * @return \Drupal\Tests\views\Unit\Plugin\field\FieldPluginBaseTestField
   *   The test field.
   */
  protected function setupTestField(array $options = []) {
    $field = new FieldPluginBaseTestField($this->configuration, $this->pluginId, $this->pluginDefinition);
    $field->init($this->executable, $this->display, $options);
    $field->setLinkGenerator($this->linkGenerator);
    return $field;
  }

  /**
   * @covers ::getRenderTokens
   */
  public function testGetRenderTokensWithoutFieldsAndArguments(): void {
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
  public function testGetRenderTokensWithoutArguments(): void {
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
  public function testGetRenderTokensWithArguments(): void {
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

  /**
   * @dataProvider providerTestGetRenderTokensWithQuery
   * @covers ::getRenderTokens
   * @covers ::getTokenValuesRecursive
   */
  public function testGetRenderTokensWithQuery(array $query_params, array $expected): void {
    $request = new Request($query_params);
    $this->executable->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);

    $field = $this->setupTestField(['id' => 'id']);
    $field->last_render = 'last rendered output';
    $this->display->expects($this->any())
      ->method('getHandlers')
      ->willReturnMap([
        ['argument', []],
        ['field', ['id' => $field]],
      ]);

    $this->assertEquals($expected, $field->getRenderTokens([]));
  }

  /**
   * Data provider for ::testGetRenderTokensWithQuery().
   *
   * @return array
   *   Test data.
   */
  public static function providerTestGetRenderTokensWithQuery(): array {
    $data = [];
    // No query parameters.
    $data[] = [
      [],
      [
        '{{ id }}' => 'last rendered output',
      ],
    ];
    // Invalid query parameters.
    $data[] = [
      [
        '&invalid' => [
          'a' => 1,
          'b' => [1, 2],
          1 => 2,
        ],
        'invalid.entry' => 'ignore me',
      ],
      [
        '{{ id }}' => 'last rendered output',
      ],
    ];
    // Process only valid query parameters.
    $data[] = [
      [
        'foo' => [
          'a' => 'value',
          'b' => 'value',
          'c.d' => 'invalid argument',
          '&invalid' => 'invalid argument',
        ],
        'bar' => [
          'a' => 'value',
          'b' => [
            'c' => 'value',
          ],
        ],
      ],
      [
        '{{ id }}' => 'last rendered output',
        '{{ arguments.foo.a }}' => 'value',
        '{{ arguments.foo.b }}' => 'value',
        '{{ arguments.bar.a }}' => 'value',
        '{{ arguments.bar.b.c }}' => 'value',
      ],
    ];
    // Supports numeric keys.
    $data[] = [
      [
        'multiple' => [
          1,
          2,
          3,
        ],
        1 => '',
        3 => '&amp; encoded_value',
      ],
      [
        '{{ id }}' => 'last rendered output',
        '{{ arguments.multiple.0 }}' => '1',
        '{{ arguments.multiple.1 }}' => '2',
        '{{ arguments.multiple.2 }}' => '3',
        '{{ arguments.1 }}' => '',
        '{{ arguments.3 }}' => '& encoded_value',
      ],
    ];

    return $data;
  }

  /**
   * Ensures proper token replacement when generating CSS classes.
   *
   * @covers ::elementClasses
   * @covers ::elementLabelClasses
   * @covers ::elementWrapperClasses
   */
  public function testElementClassesWithTokens(): void {
    $functions = [
      'elementClasses' => 'element_class',
      'elementLabelClasses' => 'element_label_class',
      'elementWrapperClasses' => 'element_wrapper_class',
    ];

    $tokens = ['test_token' => 'foo'];
    $test_class = 'test-class-without-token test-class-with-{{ test_token }}-token';
    $expected_result = 'test-class-without-token test-class-with-foo-token';

    // Inline template to render the tokens.
    $build = [
      '#type' => 'inline_template',
      '#template' => $test_class,
      '#context' => $tokens,
      '#post_render' => [function () {}],
    ];

    // We're not testing the token rendering itself, just that the function
    // being tested correctly handles tokens when generating the element's class
    // attribute.
    $this->renderer->expects($this->any())
      ->method('renderInIsolation')
      ->with($build)
      ->willReturn($expected_result);

    foreach ($functions as $callable => $option_name) {
      $field = $this->setupTestField([$option_name => $test_class]);
      $field->view->style_plugin = new \stdClass();
      $field->view->style_plugin->render_tokens[] = $tokens;

      $result = $field->{$callable}(0);
      $this->assertEquals($expected_result, $result);
    }
  }

}

class FieldPluginBaseTestField extends FieldPluginBase {

  public function setLinkGenerator(LinkGeneratorInterface $link_generator) {
    $this->linkGenerator = $link_generator;
  }

}

// @todo Remove as part of https://www.example.com/node/2529170.
namespace Drupal\views\Plugin\views\field;

if (!function_exists('base_path')) {

  function base_path() {
    return '/';
  }

}
