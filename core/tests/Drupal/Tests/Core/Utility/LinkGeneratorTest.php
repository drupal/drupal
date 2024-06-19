<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Utility;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\GeneratedButton;
use Drupal\Core\GeneratedNoLink;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Language\Language;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Utility\LinkGenerator
 * @group Utility
 */
class LinkGeneratorTest extends UnitTestCase {

  /**
   * The tested link generator.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * The mocked URL generator.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked module handler.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked renderer service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The mocked URL Assembler service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected $urlAssembler;

  /**
   * Contains the LinkGenerator default options.
   */
  protected $defaultOptions = [
    'query' => [],
    'language' => NULL,
    'set_active_class' => FALSE,
    'absolute' => FALSE,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->urlGenerator = $this->getMockBuilder('\Drupal\Core\Routing\UrlGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->renderer = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $this->linkGenerator = new LinkGenerator($this->urlGenerator, $this->moduleHandler, $this->renderer);
    $this->urlAssembler = $this->createMock('\Drupal\Core\Utility\UnroutedUrlAssemblerInterface');
  }

  /**
   * Provides test data for testing the link method.
   *
   * @see \Drupal\Tests\Core\Utility\LinkGeneratorTest::testGenerateHrefs()
   *
   * @return array
   *   Returns some test data.
   */
  public static function providerTestGenerateHrefs() {
    return [
      // Test that the URL returned by the URL generator is used.
      ['test_route_1', [], FALSE, '/test-route-1'],
        // Test that $parameters is passed to the URL generator.
      ['test_route_2', ['value' => 'example'], FALSE, '/test-route-2/example'],
        // Test that the 'absolute' option is passed to the URL generator.
      ['test_route_3', [], TRUE, 'http://example.com/test-route-3'],
    ];
  }

  /**
   * Tests the link method with certain hrefs.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   * @see \Drupal\Tests\Core\Utility\LinkGeneratorTest::providerTestGenerate()
   *
   * @dataProvider providerTestGenerateHrefs
   */
  public function testGenerateHrefs($route_name, array $parameters, $absolute, $expected_url): void {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with($route_name, $parameters, ['absolute' => $absolute] + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl($expected_url));
    $this->moduleHandler->expects($this->once())
      ->method('alter');

    $url = new Url($route_name, $parameters, ['absolute' => $absolute]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => ['href' => $expected_url],
    ], $result);
  }

  /**
   * Tests the generate() method with a route.
   *
   * @covers ::generate
   */
  public function testGenerate(): void {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', [], ['fragment' => 'the-fragment'] + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1#the-fragment'));

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('link', $this->isType('array'));

    $url = new Url('test_route_1', [], ['fragment' => 'the-fragment']);
    $url->setUrlGenerator($this->urlGenerator);

    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'href' => '/test-route-1#the-fragment',
      ],
      'content' => 'Test',
    ], $result);
  }

  /**
   * Tests the generate() method with the <nolink> route.
   *
   * The set_active_class option is set to TRUE to ensure we do not get the
   * active class and the data-drupal-link-system-path attribute.
   *
   * @covers ::generate
   */
  public function testGenerateNoLink(): void {
    $this->urlGenerator->expects($this->never())
      ->method('generateFromRoute');
    $this->moduleHandler->expects($this->exactly(2))
      ->method('alter')
      ->with('link', $this->isType('array'));

    $url = Url::fromRoute('<nolink>');
    $url->setUrlGenerator($this->urlGenerator);
    $url->setOption('set_active_class', TRUE);

    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertInstanceOf(GeneratedNoLink::class, $result);
    $this->assertSame('<span>Test</span>', (string) $result);

    // Validate removal of hreflang attributes.
    $url = Url::fromRoute('<nolink>', [], [
      'language' => new Language(['id' => 'de']),
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test With Language', $url);
    $this->assertSame('<span>Test With Language</span>', (string) $result);
  }

  /**
   * Tests the generate() method with the <none> route.
   *
   * The set_active_class option is set to TRUE to ensure we do not get the
   * active class and the data-drupal-link-system-path attribute.
   *
   * @covers ::generate
   */
  public function testGenerateNone(): void {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('<none>', [], ['set_active_class' => TRUE] + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl(''));

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('link', $this->isType('array'));

    $url = Url::fromRoute('<none>');
    $url->setUrlGenerator($this->urlGenerator);
    $url->setOption('set_active_class', TRUE);

    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertSame('<a href="">Test</a>', (string) $result);
  }

  /**
   * Tests the generate() method with the <button> route.
   *
   * @covers ::generate
   */
  public function testGenerateButton(): void {
    $this->urlGenerator->expects($this->never())
      ->method('generateFromRoute');
    $this->moduleHandler->expects($this->exactly(2))
      ->method('alter')
      ->with('link', $this->isType('array'));

    $url = Url::fromRoute('<button>');
    $url->setUrlGenerator($this->urlGenerator);

    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertInstanceOf(GeneratedButton::class, $result);
    $this->assertSame('<button type="button">Test</button>', (string) $result);

    // Validate removal of hreflang attributes.
    $url = new Url('<button>', [], [
      'language' => new Language(['id' => 'de']),
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test With Language', $url);
    $this->assertSame('<button type="button">Test With Language</button>', (string) $result);
  }

  /**
   * Tests the generate() method with an external URL.
   *
   * The set_active_class option is set to TRUE to ensure this does not cause
   * an error together with an external URL.
   *
   * @covers ::generate
   */
  public function testGenerateExternal(): void {
    $this->urlAssembler->expects($this->once())
      ->method('assemble')
      ->with('https://www.drupal.org', ['set_active_class' => TRUE, 'external' => TRUE] + $this->defaultOptions)
      ->willReturnArgument(0);

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('link', $this->isType('array'));

    $this->urlAssembler->expects($this->once())
      ->method('assemble')
      ->with('https://www.drupal.org', ['set_active_class' => TRUE, 'external' => TRUE] + $this->defaultOptions)
      ->willReturnArgument(0);

    $url = Url::fromUri('https://www.drupal.org');
    $url->setUrlGenerator($this->urlGenerator);
    $url->setUnroutedUrlAssembler($this->urlAssembler);
    $url->setOption('set_active_class', TRUE);

    $result = $this->linkGenerator->generate('Drupal', $url);
    $this->assertLink([
      'attributes' => [
        'href' => 'https://www.drupal.org',
      ],
      'content' => 'Drupal',
    ], $result);
  }

  /**
   * Tests the generate() method with a URL containing double quotes.
   *
   * @covers ::generate
   */
  public function testGenerateUrlWithQuotes(): void {
    $this->urlAssembler->expects($this->once())
      ->method('assemble')
      ->with('base:example', ['query' => ['foo' => '"bar"', 'zoo' => 'baz']] + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/example?foo=%22bar%22&zoo=baz'));

    $path_validator = $this->createMock('Drupal\Core\Path\PathValidatorInterface');
    $container_builder = new ContainerBuilder();
    $container_builder->set('path.validator', $path_validator);
    \Drupal::setContainer($container_builder);

    $path = '/example?foo="bar"&zoo=baz';
    $url = Url::fromUserInput($path);
    $url->setUrlGenerator($this->urlGenerator);
    $url->setUnroutedUrlAssembler($this->urlAssembler);

    $result = $this->linkGenerator->generate('Drupal', $url);

    $this->assertLink([
      'attributes' => [
        'href' => '/example?foo=%22bar%22&zoo=baz',
      ],
      'content' => 'Drupal',
    ], $result, 1);
  }

  /**
   * Tests the link method with additional attributes.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateAttributes(): void {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', [], $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1'));

    // Test that HTML attributes are added to the anchor.
    $url = new Url('test_route_1', [], [
      'attributes' => ['title' => 'Tooltip'],
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'href' => '/test-route-1',
        'title' => 'Tooltip',
      ],
    ], $result);
  }

  /**
   * Tests the link method with passed query options.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateQuery(): void {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', [], ['query' => ['test' => 'value']] + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1?test=value'));

    $url = new Url('test_route_1', [], [
      'query' => ['test' => 'value'],
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'href' => '/test-route-1?test=value',
      ],
    ], $result);
  }

  /**
   * Tests the link method with passed query options via parameters.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateParametersAsQuery(): void {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', ['test' => 'value'], $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1?test=value'));

    $url = new Url('test_route_1', ['test' => 'value'], []);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'href' => '/test-route-1?test=value',
      ],
    ], $result);
  }

  /**
   * Tests the link method with arbitrary passed options.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateOptions(): void {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', [], ['key' => 'value'] + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1?test=value'));
    $url = new Url('test_route_1', [], [
      'key' => 'value',
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'href' => '/test-route-1?test=value',
      ],
    ], $result);
  }

  /**
   * Tests the link method with a script tab.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateXss(): void {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_4', [], $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-4'));

    // Test that HTML link text is escaped by default.
    $url = new Url('test_route_4');
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate("<script>alert('XSS!')</script>", $url);
    $this->assertNoXPathResults('//a[@href="/test-route-4"]/script', (string) $result);
  }

  /**
   * Tests the link method with html.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateWithHtml(): void {
    $this->urlGenerator->expects($this->exactly(2))
      ->method('generateFromRoute')
      ->with('test_route_5', [], $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-5'));

    // Test that HTML tags are stripped from the 'title' attribute.
    $url = new Url('test_route_5', [], [
      'attributes' => ['title' => '<em>HTML Tooltip</em>'],
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'href' => '/test-route-5',
        'title' => 'HTML Tooltip',
      ],
    ], $result);

    // Test that safe HTML is output inside the anchor tag unescaped. The
    // Markup::create() call is an intentional unit test for the interaction
    // between MarkupInterface and the LinkGenerator.
    $url = new Url('test_route_5', []);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate(Markup::create('<em>HTML output</em>'), $url);
    $this->assertLink([
      'attributes' => ['href' => '/test-route-5'],
      'child' => [
        'tag' => 'em',
      ],
    ], $result);
    $this->assertStringContainsString('<em>HTML output</em>', (string) $result);
  }

  /**
   * Tests the active class on the link method.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateActive(): void {
    $this->urlGenerator->expects($this->exactly(5))
      ->method('generateFromRoute')
      ->willReturnCallback(function ($name, $parameters = [], $options = [], $collect_bubbleable_metadata = FALSE) {
        switch ($name) {
          case 'test_route_1':
            return (new GeneratedUrl())->setGeneratedUrl('/test-route-1');

          case 'test_route_3':
            return (new GeneratedUrl())->setGeneratedUrl('/test-route-3');

          case 'test_route_4':
            if ($parameters['object'] == '1') {
              return (new GeneratedUrl())->setGeneratedUrl('/test-route-4/1');
            }
        }
      });

    $this->urlGenerator->expects($this->exactly(4))
      ->method('getPathFromRoute')
      ->willReturnMap([
        ['test_route_1', [], 'test-route-1'],
        ['test_route_3', [], 'test-route-3'],
        ['test_route_4', ['object' => '1'], 'test-route-4/1'],
      ]);

    $this->moduleHandler->expects($this->exactly(5))
      ->method('alter');

    // Render a link.
    $url = new Url('test_route_1', [], ['set_active_class' => TRUE]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => ['data-drupal-link-system-path' => 'test-route-1'],
    ], $result);

    // Render a link with the set_active_class option disabled.
    $url = new Url('test_route_1', [], ['set_active_class' => FALSE]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertNoXPathResults('//a[@data-drupal-link-system-path="test-route-1"]', (string) $result);

    // Render a link with an associated language.
    $url = new Url('test_route_1', [], [
      'language' => new Language(['id' => 'de']),
      'set_active_class' => TRUE,
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'data-drupal-link-system-path' => 'test-route-1',
        'hreflang' => 'de',
      ],
    ], $result);

    // Render a link with a query parameter.
    $url = new Url('test_route_3', [], [
      'query' => ['value' => 'example_1'],
      'set_active_class' => TRUE,
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'data-drupal-link-system-path' => 'test-route-3',
        'data-drupal-link-query' => '{"value":"example_1"}',
      ],
    ], $result);

    // Render a link with route parameters and a query parameter.
    $url = new Url('test_route_4', ['object' => '1'], [
      'query' => ['value' => 'example_1'],
      'set_active_class' => TRUE,
    ]);
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink([
      'attributes' => [
        'data-drupal-link-system-path' => 'test-route-4/1',
        'data-drupal-link-query' => '{"value":"example_1"}',
      ],
    ], $result);
  }

  /**
   * Tests the LinkGenerator's support for collecting bubbleable metadata.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   * @see \Drupal\Core\Utility\LinkGenerator::generateFromLink()
   *
   * @group legacy
   */
  public function testGenerateBubbleableMetadata(): void {
    $options = ['query' => [], 'language' => NULL, 'set_active_class' => FALSE, 'absolute' => FALSE];
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturnMap([
        [
          'test_route_1',
          [],
          $options,
          TRUE,
          (new GeneratedUrl())->setGeneratedUrl('/test-route-1'),
        ],
      ]);

    $url = new Url('test_route_1');
    $url->setUrlGenerator($this->urlGenerator);
    $expected_link_markup = '<a href="/test-route-1">Test</a>';

    // Test ::generate().
    $this->assertSame($expected_link_markup, (string) $this->linkGenerator->generate('Test', $url));
    $generated_link = $this->linkGenerator->generate('Test', $url);
    $this->assertSame($expected_link_markup, (string) $generated_link->getGeneratedLink());
    $this->assertInstanceOf('\Drupal\Core\Render\BubbleableMetadata', $generated_link);

    // Test ::generateFromLink().
    $this->expectDeprecation('\Drupal\Core\Utility\LinkGeneratorInterface::generateFromLink() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Utility\LinkGeneratorInterface::generate() instead. See https://www.drupal.org/node/3342992');
    $link = new Link('Test', $url);
    $this->assertSame($expected_link_markup, (string) $this->linkGenerator->generateFromLink($link));
    $generated_link = $this->linkGenerator->generateFromLink($link);
    $this->assertSame($expected_link_markup, (string) $generated_link->getGeneratedLink());
    $this->assertInstanceOf('\Drupal\Core\Render\BubbleableMetadata', $generated_link);
  }

  /**
   * Tests altering the URL object using hook_link_alter().
   *
   * @covers ::generate
   */
  public function testGenerateWithAlterHook(): void {
    $options = ['query' => [], 'language' => NULL, 'set_active_class' => FALSE, 'absolute' => FALSE];
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturnMap([
        [
          'test_route_1',
          [],
          $options,
          TRUE,
          (new GeneratedUrl())->setGeneratedUrl('/test-route-1'),
        ],
        [
          'test_route_2',
          [],
          $options,
          TRUE,
          (new GeneratedUrl())->setGeneratedUrl('/test-route-2'),
        ],
      ]);

    $url = new Url('test_route_2');
    $url->setUrlGenerator($this->urlGenerator);

    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('alter')
      ->willReturnCallback(function ($hook, &$options) {
        $options['url'] = (new Url('test_route_1'))->setUrlGenerator($this->urlGenerator);
      });

    $expected_link_markup = '<a href="/test-route-1">Test</a>';
    $this->assertEquals($expected_link_markup, (string) $this->linkGenerator->generate('Test', $url)->getGeneratedLink());
  }

  /**
   * Tests whether rendering the same link twice works.
   *
   * This is a regression test for https://www.drupal.org/node/2842399.
   */
  public function testGenerateTwice(): void {
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/'));

    $url = Url::fromRoute('<front>', [], ['attributes' => ['class' => ['foo', 'bar']]]);
    $url->setUrlGenerator($this->urlGenerator);

    $link = Link::fromTextAndUrl('text', $url);
    $link->setLinkGenerator($this->linkGenerator);
    $output = $link->toString() . $link->toString();
    $this->assertEquals('<a href="/" class="foo bar">text</a><a href="/" class="foo bar">text</a>', $output);
  }

  /**
   * Checks that a link with certain properties exists in a given HTML snippet.
   *
   * @param array $properties
   *   An associative array of link properties, with the following keys:
   *   - attributes: optional array of HTML attributes that should be present.
   *   - content: optional link content.
   * @param \Drupal\Component\Render\MarkupInterface $html
   *   The HTML to check.
   * @param int $count
   *   How many times the link should be present in the HTML. Defaults to 1.
   *
   * @internal
   */
  public static function assertLink(array $properties, MarkupInterface $html, int $count = 1): void {
    // Provide default values.
    $properties += ['attributes' => []];

    // Create an XPath query that selects a link element.
    $query = '//a';

    // Append XPath predicates for the attributes and content text.
    $predicates = [];
    foreach ($properties['attributes'] as $attribute => $value) {
      $predicates[] = "@$attribute='$value'";
    }
    if (!empty($properties['content'])) {
      $predicates[] = "contains(.,'{$properties['content']}')";
    }
    if (!empty($predicates)) {
      $query .= '[' . implode(' and ', $predicates) . ']';
    }

    // Execute the query.
    $document = Html::load($html);
    $xpath = new \DOMXPath($document);

    self::assertEquals($count, $xpath->query($query)->length);
  }

  /**
   * Checks that the given XPath query has no results in a given HTML snippet.
   *
   * @param string $query
   *   The XPath query to execute.
   * @param string $html
   *   The HTML snippet to check.
   *
   * @internal
   */
  protected function assertNoXPathResults(string $query, string $html): void {
    $document = Html::load($html);
    $xpath = new \DOMXPath($document);

    self::assertFalse((bool) $xpath->query($query)->length);
  }

}
