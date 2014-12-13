<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Utility\LinkGeneratorTest.
 */

namespace Drupal\Tests\Core\Utility {

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Language\Language;
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
   * The mocked renderer.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
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
      ->will($this->returnValue($expected_url));

    $this->moduleHandler->expects($this->once())
      ->method('alter');

    $url = new Url($route_name, $parameters, array('absolute' => $absolute));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
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
      ->will($this->returnValue('/test-route-1#the-fragment'));

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('link', $this->isType('array'));

    $url = new Url('test_route_1', array(), array('fragment' => 'the-fragment'));
    $url->setUrlGenerator($this->urlGenerator);

    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
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
      ->with('http://drupal.org', array('set_active_class' => TRUE, 'external' => TRUE) + $this->defaultOptions)
      ->will($this->returnArgument(0));

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('link', $this->isType('array'));

    $this->urlAssembler->expects($this->once())
      ->method('assemble')
      ->with('http://drupal.org', array('set_active_class' => TRUE, 'external' => TRUE) + $this->defaultOptions)
      ->willReturnArgument(0);

    $url = Url::fromUri('http://drupal.org');
    $url->setUrlGenerator($this->urlGenerator);
    $url->setUnroutedUrlAssembler($this->urlAssembler);
    $url->setOption('set_active_class', TRUE);

    $result = $this->linkGenerator->generate('Drupal', $url);
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array(
        'href' => 'http://drupal.org',
      ),
      'content' => 'Drupal',
    ), $result);
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
      ->will($this->returnValue(
        '/test-route-1'
      ));

    // Test that HTML attributes are added to the anchor.
    $url = new Url('test_route_1', array(), array(
      'attributes' => array('title' => 'Tooltip'),
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
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
      ->will($this->returnValue(
        '/test-route-1?test=value'
      ));

    $url = new Url('test_route_1', array(), array(
      'query' => array('test' => 'value'),
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
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
      ->will($this->returnValue(
        '/test-route-1?test=value'
      ));

    $url = new Url('test_route_1', array('test' => 'value'), array());
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
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
      ->will($this->returnValue(
        '/test-route-1?test=value'
      ));

    $url = new Url('test_route_1', array(), array(
      'key' => 'value',
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
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
      ->will($this->returnValue(
        '/test-route-4'
      ));

    // Test that HTML link text is escaped by default.
    $url = new Url('test_route_4');
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate("<script>alert('XSS!')</script>", $url);
    $this->assertNotTag(array(
      'tag' => 'a',
      'attributes' => array('href' => '/test-route-4'),
      'child' => array(
        'tag' => 'script',
      ),
    ), $result);
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
      ->will($this->returnValue(
        '/test-route-5'
      ));
    $this->urlGenerator->expects($this->at(1))
      ->method('generateFromRoute')
      ->with('test_route_5', array(), $this->defaultOptions)
      ->will($this->returnValue(
        '/test-route-5'
      ));

    // Test that HTML tags are stripped from the 'title' attribute.
    $url = new Url('test_route_5', array(), array(
      'attributes' => array('title' => '<em>HTML Tooltip</em>'),
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array(
        'href' => '/test-route-5',
        'title' => 'HTML Tooltip',
      ),
    ), $result);

    // Test that HTML link text can be used in a render array.
    $html = '<em>HTML output</em>';
    $render_array = [
      '#type' => 'inline_template',
      '#template' => '<em>HTML output</em>',
    ];

    $this->renderer->expects($this->at(0))
      ->method('render')
      ->with($render_array)
      // Mark the HTML string as safe at the moment when the mocked render()
      // method is invoked to mimic what occurs in render(). We cannot mock
      // static methods.
      ->will($this->returnCallback(function () use ($html) {
        SafeMarkup::set($html);
        return $html;
      }));

    $url = new Url('test_route_5', array());
    $url->setUrlGenerator($this->urlGenerator);

    $result = $this->linkGenerator->generate($render_array, $url);
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array('href' => '/test-route-5'),
      'child' => array(
        'tag' => 'em',
      ),
    ), $result);
  }

  /**
   * Tests the active class on the link method.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   *
   * @todo Test that the active class is added on the front page when generating
   *   links to the front page when drupal_is_front_page() is converted to a
   *   service.
   */
  public function testGenerateActive() {
    $this->urlGenerator->expects($this->exactly(5))
      ->method('generateFromRoute')
      ->will($this->returnValueMap(array(
        array('test_route_1', array(), FALSE, '/test-route-1'),
        array('test_route_3', array(), FALSE, '/test-route-3'),
        array('test_route_4', array('object' => '1'), FALSE, '/test-route-4/1'),
      )));

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
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array('data-drupal-link-system-path' => 'test-route-1'),
    ), $result);

    // Render a link with the set_active_class option disabled.
    $url = new Url('test_route_1', array(), array('set_active_class' => FALSE));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertNotTag(array(
      'tag' => 'a',
      'attributes' => array('data-drupal-link-system-path' => 'test-route-1'),
    ), $result);

    // Render a link with an associated language.
    $url = new Url('test_route_1', array(), array(
      'language' => new Language(array('id' => 'de')),
      'set_active_class' => TRUE,
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
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
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array(
        'data-drupal-link-system-path' => 'test-route-3',
        'data-drupal-link-query' => 'regexp:/.*value.*example_1.*/',
      ),
    ), $result);

    // Render a link with route parameters and a query parameter.
    $url = new Url('test_route_4', array('object' => '1'), array(
      'query' => array('value' => 'example_1'),
      'set_active_class' => TRUE,
    ));
    $url->setUrlGenerator($this->urlGenerator);
    $result = $this->linkGenerator->generate('Test', $url);
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array(
        'data-drupal-link-system-path' => 'test-route-4/1',
        'data-drupal-link-query' => 'regexp:/.*value.*example_1.*/',
      ),
    ), $result);
  }

}

}
namespace {
  // @todo Remove this once there is a service for drupal_is_front_page().
  if (!function_exists('drupal_is_front_page')) {
    function drupal_is_front_page() {
      return FALSE;
    }
  }
}
