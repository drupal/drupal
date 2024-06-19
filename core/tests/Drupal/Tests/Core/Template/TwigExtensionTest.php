<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Template;

// cspell:ignore mila

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Template\Loader\StringLoader;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Template\TwigExtension;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Node\Expression\FilterExpression;
use Twig\Source;

/**
 * Tests the twig extension.
 *
 * @group Template
 *
 * @coversDefaultClass \Drupal\Core\Template\TwigExtension
 */
class TwigExtensionTest extends UnitTestCase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $dateFormatter;

  /**
   * The system under test.
   *
   * @var \Drupal\Core\Template\TwigExtension
   */
  protected $systemUnderTest;

  /**
   * The file URL generator mock.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->renderer = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $this->urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');
    $this->themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $this->dateFormatter = $this->createMock('\Drupal\Core\Datetime\DateFormatterInterface');
    $this->fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);

    $this->systemUnderTest = new TwigExtension($this->renderer, $this->urlGenerator, $this->themeManager, $this->dateFormatter, $this->fileUrlGenerator);
  }

  /**
   * Tests the escaping.
   *
   * @dataProvider providerTestEscaping
   */
  public function testEscaping($template, $expected): void {
    $loader = new FilesystemLoader();
    $twig = new Environment($loader, [
      'debug' => TRUE,
      'cache' => FALSE,
      'autoescape' => 'html',
      'optimizations' => 0,
    ]);
    $twig->addExtension($this->systemUnderTest);

    $name = '__string_template_test__';
    $nodes = $twig->parse($twig->tokenize(new Source($template, $name)));

    $this->assertSame($expected, $nodes->getNode('body')
      ->getNode('0')
      ->getNode('expr') instanceof FilterExpression);
  }

  /**
   * Provides tests data for testEscaping.
   *
   * @return array
   *   An array of test data each containing of a twig template string and
   *   a boolean expecting whether the path will be safe.
   */
  public static function providerTestEscaping() {
    return [
      ['{{ path("foo") }}', FALSE],
      ['{{ path("foo", {}) }}', FALSE],
      ['{{ path("foo", { foo: "foo" }) }}', FALSE],
      ['{{ path("foo", foo) }}', TRUE],
      ['{{ path("foo", { foo: foo }) }}', TRUE],
      ['{{ path("foo", { foo: ["foo", "bar"] }) }}', TRUE],
      ['{{ path("foo", { foo: "foo", bar: "bar" }) }}', TRUE],
      ['{{ path(name = "foo", parameters = {}) }}', FALSE],
      ['{{ path(name = "foo", parameters = { foo: "foo" }) }}', FALSE],
      ['{{ path(name = "foo", parameters = foo) }}', TRUE],
      [
        '{{ path(name = "foo", parameters = { foo: ["foo", "bar"] }) }}',
        TRUE,
      ],
      ['{{ path(name = "foo", parameters = { foo: foo }) }}', TRUE],
      [
        '{{ path(name = "foo", parameters = { foo: "foo", bar: "bar" }) }}',
        TRUE,
      ],
    ];
  }

  /**
   * Tests the active_theme function.
   */
  public function testActiveTheme(): void {
    $active_theme = $this->getMockBuilder('\Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $active_theme->expects($this->once())
      ->method('getName')
      ->willReturn('test_theme');
    $this->themeManager->expects($this->once())
      ->method('getActiveTheme')
      ->willReturn($active_theme);

    $loader = new StringLoader();
    $twig = new Environment($loader);
    $twig->addExtension($this->systemUnderTest);
    $result = $twig->render('{{ active_theme() }}');
    $this->assertEquals('test_theme', $result);
  }

  /**
   * Tests the format_date filter.
   */
  public function testFormatDate(): void {
    $this->dateFormatter->expects($this->exactly(1))
      ->method('format')
      ->willReturnCallback(function ($timestamp) {
        return date('Y-m-d', $timestamp);
      });

    $loader = new StringLoader();
    $twig = new Environment($loader);
    $twig->addExtension($this->systemUnderTest);
    $timestamp = strtotime('1978-11-19');
    $result = $twig->render('{{ time|format_date("html_date") }}', ['time' => $timestamp]);
    $this->assertEquals('1978-11-19', $result);
  }

  /**
   * Tests the file_url filter.
   */
  public function testFileUrl(): void {
    $this->fileUrlGenerator->expects($this->once())
      ->method('generateString')
      ->with('public://picture.jpg')
      ->willReturn('sites/default/files/picture.jpg');

    $loader = new StringLoader();
    $twig = new Environment($loader);
    $twig->addExtension($this->systemUnderTest);
    $result = $twig->render('{{ file_url(file) }}', ['file' => 'public://picture.jpg']);
    $this->assertEquals('sites/default/files/picture.jpg', $result);
  }

  /**
   * Tests the active_theme_path function.
   */
  public function testActiveThemePath(): void {
    $active_theme = $this->getMockBuilder('\Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $active_theme
      ->expects($this->once())
      ->method('getPath')
      ->willReturn('foo/bar');
    $this->themeManager->expects($this->once())
      ->method('getActiveTheme')
      ->willReturn($active_theme);

    $loader = new StringLoader();
    $twig = new Environment($loader);
    $twig->addExtension($this->systemUnderTest);
    $result = $twig->render('{{ active_theme_path() }}');
    $this->assertEquals('foo/bar', $result);
  }

  /**
   * Tests the escaping of objects implementing MarkupInterface.
   *
   * @covers ::escapeFilter
   */
  public function testSafeStringEscaping(): void {
    $loader = new FilesystemLoader();
    $twig = new Environment($loader, [
      'debug' => TRUE,
      'cache' => FALSE,
      'autoescape' => 'html',
      'optimizations' => 0,
    ]);

    // By default, TwigExtension will attempt to cast objects to strings.
    // Ensure objects that implement MarkupInterface are unchanged.
    $safe_string = $this->createMock('\Drupal\Component\Render\MarkupInterface');
    $this->assertSame($safe_string, $this->systemUnderTest->escapeFilter($twig, $safe_string, 'html', 'UTF-8', TRUE));

    // Ensure objects that do not implement MarkupInterface are escaped.
    $string_object = new TwigExtensionTestString("<script>alert('here');</script>");
    $this->assertSame('&lt;script&gt;alert(&#039;here&#039;);&lt;/script&gt;', $this->systemUnderTest->escapeFilter($twig, $string_object, 'html', 'UTF-8', TRUE));
  }

  /**
   * @covers ::safeJoin
   */
  public function testSafeJoin(): void {
    $this->renderer->expects($this->any())
      ->method('render')
      ->with(['#markup' => '<strong>will be rendered</strong>', '#printed' => FALSE])
      ->willReturn('<strong>will be rendered</strong>');

    $twig_environment = $this->prophesize(TwigEnvironment::class)->reveal();

    // Simulate t().
    $markup = $this->prophesize(TranslatableMarkup::class);
    $markup->__toString()->willReturn('<em>will be markup</em>');
    $markup = $markup->reveal();

    $items = [
      '<em>will be escaped</em>',
      $markup,
      ['#markup' => '<strong>will be rendered</strong>'],
    ];
    $result = $this->systemUnderTest->safeJoin($twig_environment, $items, '<br/>');
    $this->assertEquals('&lt;em&gt;will be escaped&lt;/em&gt;<br/><em>will be markup</em><br/><strong>will be rendered</strong>', $result);

    // Ensure safe_join Twig filter supports Traversable variables.
    $items = new \ArrayObject([
      '<em>will be escaped</em>',
      $markup,
      ['#markup' => '<strong>will be rendered</strong>'],
    ]);
    $result = $this->systemUnderTest->safeJoin($twig_environment, $items, ', ');
    $this->assertEquals('&lt;em&gt;will be escaped&lt;/em&gt;, <em>will be markup</em>, <strong>will be rendered</strong>', $result);

    // Ensure safe_join Twig filter supports empty variables.
    $items = NULL;
    $result = $this->systemUnderTest->safeJoin($twig_environment, $items, '<br>');
    $this->assertEmpty($result);
  }

  /**
   * @dataProvider providerTestRenderVar
   */
  public function testRenderVar($result, $input): void {
    $this->renderer->expects($this->any())
      ->method('render')
      ->with($result += ['#printed' => FALSE])
      ->willReturn('Rendered output');

    $this->assertEquals('Rendered output', $this->systemUnderTest->renderVar($input));
  }

  public static function providerTestRenderVar() {
    $data = [];

    $renderable = (new Prophet())->prophesize(RenderableInterface::class);
    $render_array = ['#type' => 'test', '#var' => 'giraffe'];
    $renderable->toRenderable()->willReturn($render_array);
    $data['renderable'] = [$render_array, $renderable->reveal()];

    return $data;
  }

  /**
   * @covers ::escapeFilter
   * @covers ::bubbleArgMetadata
   */
  public function testEscapeWithGeneratedLink(): void {
    $loader = new FilesystemLoader();
    $twig = new Environment($loader, [
      'debug' => TRUE,
      'cache' => FALSE,
      'autoescape' => 'html',
      'optimizations' => 0,
    ]);

    $twig->addExtension($this->systemUnderTest);
    $link = new GeneratedLink();
    $link->setGeneratedLink('<a href="http://example.com"></a>');
    $link->addCacheTags(['foo']);
    $link->addAttachments(['library' => ['system/base']]);

    $this->renderer->expects($this->atLeastOnce())
      ->method('render')
      ->with([
        "#cache" => [
          "contexts" => [],
          "tags" => ["foo"],
          "max-age" => -1,
        ],
        "#attached" => ['library' => ['system/base']],
      ]);
    $result = $this->systemUnderTest->escapeFilter($twig, $link, 'html', NULL, TRUE);
    $this->assertEquals('<a href="http://example.com"></a>', $result);
  }

  /**
   * @covers ::renderVar
   * @covers ::bubbleArgMetadata
   */
  public function testRenderVarWithGeneratedLink(): void {
    $link = new GeneratedLink();
    $link->setGeneratedLink('<a href="http://example.com"></a>');
    $link->addCacheTags(['foo']);
    $link->addAttachments(['library' => ['system/base']]);

    $this->renderer->expects($this->atLeastOnce())
      ->method('render')
      ->with([
        "#cache" => [
          "contexts" => [],
          "tags" => ["foo"],
          "max-age" => -1,
        ],
        "#attached" => ['library' => ['system/base']],
      ]);
    $result = $this->systemUnderTest->renderVar($link);
    $this->assertEquals('<a href="http://example.com"></a>', $result);
  }

  /**
   * @covers ::renderVar
   * @dataProvider providerTestRenderVarEarlyReturn
   */
  public function testRenderVarEarlyReturn($expected, $input): void {
    $result = $this->systemUnderTest->renderVar($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for ::testRenderVarEarlyReturn().
   */
  public static function providerTestRenderVarEarlyReturn() {
    return [
      'null' => ['', NULL],
      'empty array' => ['', []],
      'float zero' => [0, 0.0],
      'float non-zero' => [10.0, 10.0],
      'int zero' => [0, 0],
      'int non-zero' => [10, 10],
      'empty string' => ['', ''],
      'string' => ['test', 'test'],
      'FALSE' => ['', FALSE],
      'TRUE' => [TRUE, TRUE],
    ];
  }

  /**
   * Tests creating attributes within a Twig template.
   *
   * @covers ::createAttribute
   */
  public function testCreateAttribute(): void {
    $name = '__string_template_test_1__';
    $loader = new ArrayLoader([$name => "{% for iteration in iterations %}<div{{ create_attribute(iteration) }}></div>{% endfor %}"]);
    $twig = new Environment($loader);
    $twig->addExtension($this->systemUnderTest);

    $iterations = [
      ['class' => ['kittens'], 'data-toggle' => 'modal', 'data-lang' => 'es'],
      ['id' => 'puppies', 'data-value' => 'foo', 'data-lang' => 'en'],
      [],
      new Attribute(),
    ];
    $result = $twig->render($name, ['iterations' => $iterations]);
    $expected = '<div class="kittens" data-toggle="modal" data-lang="es"></div><div id="puppies" data-value="foo" data-lang="en"></div><div></div><div></div>';
    $this->assertEquals($expected, $result);

    // Test default creation of empty attribute object and using its method.
    $name = '__string_template_test_2__';
    $loader = new ArrayLoader([$name => "<div{{ create_attribute().addClass('meow') }}></div>"]);
    $twig->setLoader($loader);
    $result = $twig->render($name);
    $expected = '<div class="meow"></div>';
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::getLink
   */
  public function testLinkWithOverriddenAttributes(): void {
    $url = Url::fromRoute('<front>', [], ['attributes' => ['class' => ['foo']]]);

    $build = $this->systemUnderTest->getLink('test', $url, ['class' => ['bar']]);

    $this->assertEquals(['foo', 'bar'], $build['#url']->getOption('attributes')['class']);
  }

  /**
   * Tests Twig 'add_suggestion' filter.
   *
   * @covers ::suggestThemeHook
   * @dataProvider providerTestTwigAddSuggestionFilter
   */
  public function testTwigAddSuggestionFilter($original_render_array, $suggestion, $expected_render_array): void {
    $processed_render_array = $this->systemUnderTest->suggestThemeHook($original_render_array, $suggestion);
    $this->assertEquals($expected_render_array, $processed_render_array);
  }

  /**
   * Provides data for ::testTwigAddSuggestionFilter().
   *
   * @return \Iterator
   */
  public static function providerTestTwigAddSuggestionFilter(): \Iterator {
    yield 'suggestion should be added' => [
      [
        '#theme' => 'kitten',
        '#name' => 'Mila',
      ],
      'cute',
      [
        '#theme' => [
          'kitten__cute',
          'kitten',
        ],
        '#name' => 'Mila',
      ],
    ];

    yield 'suggestion should extend existing suggestions' => [
      [
        '#theme' => 'kitten__stripy',
        '#name' => 'Mila',
      ],
      'cute',
      [
        '#theme' => [
          'kitten__stripy__cute',
          'kitten__stripy',
        ],
        '#name' => 'Mila',
      ],
    ];

    yield 'suggestion should have highest priority' => [
      [
        '#theme' => [
          'kitten__stripy',
          'kitten',
        ],
        '#name' => 'Mila',
      ],
      'cute',
      [
        '#theme' => [
          'kitten__stripy__cute',
          'kitten__cute',
          'kitten__stripy',
          'kitten',
        ],
        '#name' => 'Mila',
      ],
    ];

    yield '#printed should be removed after suggestion was added' => [
      [
        '#theme' => 'kitten',
        '#name' => 'Mila',
        '#printed' => TRUE,
      ],
      'cute',
      [
        '#theme' => [
          'kitten__cute',
          'kitten',
        ],
        '#name' => 'Mila',
      ],
    ];

    yield 'cache key should be added' => [
      [
        '#theme' => 'kitten',
        '#name' => 'Mila',
        '#cache' => [
          'keys' => [
            'kitten',
          ],
        ],
      ],
      'cute',
      [
        '#theme' => [
          'kitten__cute',
          'kitten',
        ],
        '#name' => 'Mila',
        '#cache' => [
          'keys' => [
            'kitten',
            'cute',
          ],
        ],
      ],
    ];

    yield 'null/missing content should be ignored' => [
      NULL,
      'cute',
      NULL,
    ];
  }

  /**
   * Tests Twig 'add_class' filter.
   *
   * @covers ::addClass
   * @dataProvider providerTestTwigAddClass
   */
  public function testTwigAddClass($element, $classes, $expected_result): void {
    $processed = $this->systemUnderTest->addClass($element, $classes);
    $this->assertEquals($expected_result, $processed);
  }

  /**
   * Provides data for ::testTwigAddClass().
   *
   * @return \Iterator
   */
  public static function providerTestTwigAddClass(): \Iterator {
    yield 'should add a class on element' => [
      ['#type' => 'container'],
      'my-class',
      ['#type' => 'container', '#attributes' => ['class' => ['my-class']]],
    ];

    yield 'should add a class from a array of string keys on element' => [
      ['#type' => 'container'],
      ['my-class'],
      ['#type' => 'container', '#attributes' => ['class' => ['my-class']]],
    ];

    yield 'should add a class from a Markup value' => [
      ['#type' => 'container'],
      [Markup::create('my-class')],
      ['#type' => 'container', '#attributes' => ['class' => ['my-class']]],
    ];

    yield '#printed should be removed after class(es) added' => [
      [
        '#markup' => 'This content is already is rendered',
        '#printed' => TRUE,
      ],
      '',
      [
        '#markup' => 'This content is already is rendered',
        '#attributes' => [
          'class' => [''],
        ],
      ],
    ];
  }

  /**
   * Tests Twig 'set_attribute' filter.
   *
   * @covers ::setAttribute
   * @dataProvider providerTestTwigSetAttribute
   */
  public function testTwigSetAttribute($element, $key, $value, $expected_result): void {
    $processed = $this->systemUnderTest->setAttribute($element, $key, $value);
    $this->assertEquals($expected_result, $processed);
  }

  /**
   * A data provider for ::testTwigSetAttribute().
   *
   * @return \Iterator
   */
  public static function providerTestTwigSetAttribute(): \Iterator {
    yield 'should add attributes on element' => [
      ['#theme' => 'image'],
      'title',
      'Aloha',
      [
        '#theme' => 'image',
        '#attributes' => [
          'title' => 'Aloha',
        ],
      ],
    ];

    yield 'should merge existing attributes on element' => [
      [
        '#theme' => 'image',
        '#attributes' => [
          'title' => 'Aloha',
        ],
      ],
      'title',
      'Bonjour',
      [
        '#theme' => 'image',
        '#attributes' => [
          'title' => 'Bonjour',
        ],
      ],
    ];

    yield 'should add JSON attribute value correctly on element' => [
      ['#type' => 'container'],
      'data-slider',
      Json::encode(['autoplay' => TRUE]),
      [
        '#type' => 'container',
        '#attributes' => [
          'data-slider' => '{"autoplay":true}',
        ],
      ],
    ];

    yield '#printed should be removed after setting attribute' => [
      [
        '#markup' => 'This content is already is rendered',
        '#printed' => TRUE,
      ],
      'title',
      NULL,
      [
        '#markup' => 'This content is already is rendered',
        '#attributes' => [
          'title' => NULL,
        ],
      ],
    ];
  }

}

class TwigExtensionTestString {

  protected $string;

  public function __construct($string) {
    $this->string = $string;
  }

  public function __toString() {
    return $this->string;
  }

}
