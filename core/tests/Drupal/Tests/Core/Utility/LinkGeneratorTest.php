<?php

namespace Drupal\Tests\Core\Utility {

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Language\Language;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

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
   * The mocked url generator.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked module handler.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked renderer service.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The mocked URL Assembler service.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected $urlAssembler;

  /**
   * Contains the LinkGenerator default options.
   */
  protected $defaultOptions = array(
    'query' => array(),
    'language' => NULL,
    'set_active_class' => FALSE,
    'absolute' => FALSE,
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->urlGenerator = $this->getMock('\Drupal\Core\Routing\UrlGenerator', array(), array(), '', FALSE);
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->renderer = $this->getMock('\Drupal\Core\Render\RendererInterface');
    $this->linkGenerator = new LinkGenerator($this->urlGenerator, $this->moduleHandler, $this->renderer);
    $this->urlAssembler = $this->getMock('\Drupal\Core\Utility\UnroutedUrlAssemblerInterface');
  }

  /**
   * Provides test data for testing the link method.
   *
   * @see \Drupal\Tests\Core\Utility\LinkGeneratorTest::testGenerateHrefs()
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestGenerateHrefs() {
    return array(
      // Test that the url returned by the URL generator is used.
      array('test_route_1', array(), FALSE, '/test-route-1'),
        // Test that $parameters is passed to the URL generator.
      array('test_route_2', array('value' => 'example'), FALSE, '/test-route-2/example'),
        // Test that the 'absolute' option is passed to the URL generator.
      array('test_route_3', array(), TRUE, 'http://example.com/test-route-3'),
    );
  }

  /**
   * Tests the link method with certain hrefs.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   * @see \Drupal\Tests\Core\Utility\LinkGeneratorTest::providerTestGenerate()
   *
   * @dataProvider providerTestGenerateHrefs
   */
  public function testGenerateHrefs($route_name, array $parameters, $absolute, $expected_url) {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with($route_name, $parameters, array('absolute' => $absolute) + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl($expected_url));
    $this->moduleHandler->expects($this->once())
      ->method('alter');

    $url = new Url($route_name, $parameters, array('absolute' => $absolute));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array('href' => $expected_url),
      ), $result);
  }

  /**
   * Tests the generate() method with a route.
   *
   * @covers ::generate
   */
  public function testGenerate() {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', array(), array('fragment' => 'the-fragment') + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1#the-fragment'));

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('link', $this->isType('array'));

    $url = new Url('test_route_1', array(), array('fragment' => 'the-fragment'));
    $url->setUrlGenerator($this->urlGenerator);

    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'href' => '/test-route-1#the-fragment',
      ),
      'content' => 'Test',
    ), $result);
  }

  /**
   * Tests the generate() method with an external URL.
   *
   * The set_active_class option is set to TRUE to ensure this does not cause
   * an error together with an external URL.
   *
   * @covers ::generate
   */
  public function testGenerateExternal() {
    $this->urlAssembler->expects($this->once())
      ->method('assemble')
      ->with('https://www.drupal.org', array('set_active_class' => TRUE, 'external' => TRUE) + $this->defaultOptions)
      ->will($this->returnArgument(0));

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('link', $this->isType('array'));

    $this->urlAssembler->expects($this->once())
      ->method('assemble')
      ->with('https://www.drupal.org', array('set_active_class' => TRUE, 'external' => TRUE) + $this->defaultOptions)
      ->willReturnArgument(0);

    $url = Url::fromUri('https://www.drupal.org');
    $url->setUrlGenerator($this->urlGenerator);
    $url->setUnroutedUrlAssembler($this->urlAssembler);
    $url->setOption('set_active_class', TRUE);

    $result = $this->linkGenerator->generate('Drupal', $url);
    $this->assertLink(array(
      'attributes' => array(
        'href' => 'https://www.drupal.org',
      ),
      'content' => 'Drupal',
    ), $result);
  }

  /**
   * Tests the generate() method with a url containing double quotes.
   *
   * @covers ::generate
   */
  public function testGenerateUrlWithQuotes() {
    $this->urlAssembler->expects($this->once())
      ->method('assemble')
      ->with('base:example', array('query' => array('foo' => '"bar"', 'zoo' => 'baz')) + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/example?foo=%22bar%22&zoo=baz'));

    $path_validator = $this->getMock('Drupal\Core\Path\PathValidatorInterface');
    $container_builder = new ContainerBuilder();
    $container_builder->set('path.validator', $path_validator);
    \Drupal::setContainer($container_builder);

    $path = '/example?foo="bar"&zoo=baz';
    $url = Url::fromUserInput($path);
    $url->setUrlGenerator($this->urlGenerator);
    $url->setUnroutedUrlAssembler($this->urlAssembler);

    $result = $this->linkGenerator->generate('Drupal', $url);

    $this->assertLink(array(
      'attributes' => array(
        'href' => '/example?foo=%22bar%22&zoo=baz',
      ),
      'content' => 'Drupal',
    ), $result, 1);
  }

  /**
   * Tests the link method with additional attributes.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateAttributes() {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', array(), $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1'));

    // Test that HTML attributes are added to the anchor.
    $url = new Url('test_route_1', array(), array(
      'attributes' => array('title' => 'Tooltip'),
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'href' => '/test-route-1',
        'title' => 'Tooltip',
      ),
    ), $result);
  }

  /**
   * Tests the link method with passed query options.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateQuery() {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', array(), array('query' => array('test' => 'value')) + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1?test=value'));

    $url = new Url('test_route_1', array(), array(
      'query' => array('test' => 'value'),
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'href' => '/test-route-1?test=value',
      ),
    ), $result);
  }

  /**
   * Tests the link method with passed query options via parameters.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateParametersAsQuery() {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', array('test' => 'value'), $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1?test=value'));

    $url = new Url('test_route_1', array('test' => 'value'), array());
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'href' => '/test-route-1?test=value',
      ),
    ), $result);
  }

  /**
   * Tests the link method with arbitrary passed options.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateOptions() {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_1', array(), array('key' => 'value') + $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-1?test=value'));
    $url = new Url('test_route_1', array(), array(
      'key' => 'value',
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'href' => '/test-route-1?test=value',
      ),
    ), $result);
  }

  /**
   * Tests the link method with a script tab.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateXss() {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_route_4', array(), $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-4'));

    // Test that HTML link text is escaped by default.
    $url = new Url('test_route_4');
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate("<script>alert('XSS!')</script>", $url);
    $this->assertNoXPathResults('//a[@href="/test-route-4"]/script', $result);
  }

  /**
   * Tests the link method with html.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateWithHtml() {
    $this->urlGenerator->expects($this->at(0))
      ->method('generateFromRoute')
      ->with('test_route_5', array(), $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-5'));
    $this->urlGenerator->expects($this->at(1))
      ->method('generateFromRoute')
      ->with('test_route_5', array(), $this->defaultOptions)
      ->willReturn((new GeneratedUrl())->setGeneratedUrl('/test-route-5'));

    // Test that HTML tags are stripped from the 'title' attribute.
    $url = new Url('test_route_5', array(), array(
      'attributes' => array('title' => '<em>HTML Tooltip</em>'),
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'href' => '/test-route-5',
        'title' => 'HTML Tooltip',
      ),
    ), $result);

    // Test that safe HTML is output inside the anchor tag unescaped. The
    // Markup::create() call is an intentional unit test for the interaction
    // between MarkupInterface and the LinkGenerator.
    $url = new Url('test_route_5', array());
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate(Markup::create('<em>HTML output</em>'), $url);
    $this->assertLink(array(
      'attributes' => array('href' => '/test-route-5'),
      'child' => array(
        'tag' => 'em',
      ),
    ), $result);
    $this->assertTrue(strpos($result, '<em>HTML output</em>') !== FALSE);
  }

  /**
   * Tests the active class on the link method.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function testGenerateActive() {
    $this->urlGenerator->expects($this->exactly(5))
      ->method('generateFromRoute')
      ->willReturnCallback(function($name, $parameters = array(), $options = array(), $collect_bubbleable_metadata = FALSE) {
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
      ->will($this->returnValueMap(array(
        array('test_route_1', array(), 'test-route-1'),
        array('test_route_3', array(), 'test-route-3'),
        array('test_route_4', array('object' => '1'), 'test-route-4/1'),
      )));

    $this->moduleHandler->expects($this->exactly(5))
      ->method('alter');

    // Render a link.
    $url = new Url('test_route_1', array(), array('set_active_class' => TRUE));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array('data-drupal-link-system-path' => 'test-route-1'),
    ), $result);

    // Render a link with the set_active_class option disabled.
    $url = new Url('test_route_1', array(), array('set_active_class' => FALSE));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertNoXPathResults('//a[@data-drupal-link-system-path="test-route-1"]', $result);

    // Render a link with an associated language.
    $url = new Url('test_route_1', array(), array(
      'language' => new Language(array('id' => 'de')),
      'set_active_class' => TRUE,
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'data-drupal-link-system-path' => 'test-route-1',
        'hreflang' => 'de',
      ),
    ), $result);

    // Render a link with a query parameter.
    $url = new Url('test_route_3', array(), array(
      'query' => array('value' => 'example_1'),
      'set_active_class' => TRUE,
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'data-drupal-link-system-path' => 'test-route-3',
        'data-drupal-link-query' => '{"value":"example_1"}',
      ),
    ), $result);

    // Render a link with route parameters and a query parameter.
    $url = new Url('test_route_4', array('object' => '1'), array(
      'query' => array('value' => 'example_1'),
      'set_active_class' => TRUE,
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertLink(array(
      'attributes' => array(
        'data-drupal-link-system-path' => 'test-route-4/1',
        'data-drupal-link-query' => '{"value":"example_1"}',
      ),
    ), $result);
  }

  /**
   * Tests the LinkGenerator's support for collecting bubbleable metadata.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   * @see \Drupal\Core\Utility\LinkGenerator::generateFromLink()
   */
  public function testGenerateBubbleableMetadata() {
    $options = ['query' => [], 'language' => NULL, 'set_active_class' => FALSE, 'absolute' => FALSE];
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->will($this->returnValueMap([
        ['test_route_1', [], $options, TRUE, (new GeneratedUrl())->setGeneratedUrl('/test-route-1')],
      ]));

    $url = new Url('test_route_1');
    $url->setUrlGenerator($this->urlGenerator);
    $expected_link_markup = '<a href="/test-route-1">Test</a>';

    // Test ::generate().
    $this->assertSame($expected_link_markup, (string) $this->linkGenerator->generate('Test', $url));
    $generated_link = $this->linkGenerator->generate('Test', $url);
    $this->assertSame($expected_link_markup, (string) $generated_link->getGeneratedLink());
    $this->assertInstanceOf('\Drupal\Core\Render\BubbleableMetadata', $generated_link);

    // Test ::generateFromLink().
    $link = new Link('Test', $url);
    $this->assertSame($expected_link_markup, (string) $this->linkGenerator->generateFromLink($link));
    $generated_link = $this->linkGenerator->generateFromLink($link);
    $this->assertSame($expected_link_markup, (string) $generated_link->getGeneratedLink());
    $this->assertInstanceOf('\Drupal\Core\Render\BubbleableMetadata', $generated_link);
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
   */
  public static function assertLink(array $properties, MarkupInterface $html, $count = 1) {
    // Provide default values.
    $properties += array('attributes' => array());

    // Create an XPath query that selects a link element.
    $query = '//a';

    // Append XPath predicates for the attributes and content text.
    $predicates = array();
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
    $document = new \DOMDocument;
    $document->loadHTML($html);
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
   * @return int
   *   The number of results that are found.
   */
  protected function assertNoXPathResults($query, $html) {
    $document = new \DOMDocument;
    $document->loadHTML($html);
    $xpath = new \DOMXPath($document);

    self::assertFalse((bool) $xpath->query($query)->length);
  }

}

}
