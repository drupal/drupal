<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Utility\LinkGeneratorTest.
 */

namespace Drupal\Tests\Core\Utility {

use Drupal\Core\Language\Language;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests the link generator.
 *
 * @see \Drupal\Core\Utility\LinkGenerator
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
   * The mocked language manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The mocked path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $aliasManager;

  /**
   * Contains the LinkGenerator default options.
   */
  protected $defaultOptions = array(
    'query' => array(),
    'html' => FALSE,
    'language' => NULL,
    'set_active_class' => FALSE,
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Link generator',
      'description' => 'Tests the link generator.',
      'group' => 'Common',
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->urlGenerator = $this->getMock('\Drupal\Core\Routing\UrlGenerator', array(), array(), '', FALSE);
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManager');
    $this->aliasManager = $this->getMock('\Drupal\Core\Path\AliasManagerInterface');

    $this->linkGenerator = new LinkGenerator($this->urlGenerator, $this->moduleHandler, $this->languageManager, $this->aliasManager);
  }

  /**
   * Setup a proper language manager.
   */
  public function setUpLanguageManager() {
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->will($this->returnValue(new Language(array('id' => 'en'))));
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
  public function testGenerateHrefs($route_name, array $parameters, $absolute, $url) {
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with($route_name, $parameters, array('absolute' => $absolute) + $this->defaultOptions)
      ->will($this->returnValue($url));

    $this->moduleHandler->expects($this->once())
      ->method('alter');

    $this->setUpLanguageManager();
    $request = new Request();
    $this->linkGenerator->setRequest($request);

    $result = $this->linkGenerator->generate('Test', $route_name, $parameters, array('absolute' => $absolute));
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array('href' => $url),
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
    $result = $this->linkGenerator->generate('Test', 'test_route_1', array(), array(
      'attributes' => array('title' => 'Tooltip'),
    ));
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

    $result = $this->linkGenerator->generate('Test', 'test_route_1', array(), array(
      'query' => array('test' => 'value'),
    ));
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

    $result = $this->linkGenerator->generate('Test', 'test_route_1', array('test' => 'value'), array());
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

    $result = $this->linkGenerator->generate('Test', 'test_route_1', array(), array(
      'key' => 'value',
    ));
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
    $result = $this->linkGenerator->generate("<script>alert('XSS!')</script>", 'test_route_4');
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
      ->with('test_route_5', array(), array('html' => TRUE) + $this->defaultOptions)
      ->will($this->returnValue(
        '/test-route-5'
      ));

    // Test that HTML tags are stripped from the 'title' attribute.
    $result = $this->linkGenerator->generate('Test', 'test_route_5', array(), array(
      'attributes' => array('title' => '<em>HTML Tooltip</em>'),
    ));
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array(
        'href' => '/test-route-5',
        'title' => 'HTML Tooltip',
      ),
    ), $result);

    // Test that the 'html' option allows unsanitized HTML link text.
    $result = $this->linkGenerator->generate('<em>HTML output</em>', 'test_route_5', array(), array('html' => TRUE));
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
    $this->urlGenerator->expects($this->exactly(8))
      ->method('generateFromRoute')
      ->will($this->returnValueMap(array(
        array('test_route_1', array(), FALSE, '/test-route-1'),
        array('test_route_3', array(), FALSE, '/test-route-3'),
        array('test_route_4', array('object' => '1'), FALSE, '/test-route-4/1'),
      )));

    $this->urlGenerator->expects($this->exactly(7))
      ->method('getPathFromRoute')
      ->will($this->returnValueMap(array(
        array('test_route_1', array(), 'test-route-1'),
        array('test_route_3', array(), 'test-route-3'),
        array('test_route_4', array('object' => '1'), 'test-route-4/1'),
      )));

    $this->aliasManager->expects($this->exactly(7))
      ->method('getSystemPath')
      ->will($this->returnValueMap(array(
        array('test-route-1', NULL, 'test-route-1'),
        array('test-route-3', NULL, 'test-route-3'),
        array('test-route-4/1', NULL, 'test-route-4/1'),
      )));

    $this->moduleHandler->expects($this->exactly(8))
      ->method('alter');

    $this->setUpLanguageManager();

    // Render a link with a path different from the current path.
    $request = new Request(array(), array(), array('system_path' => 'test-route-2'));
    $this->linkGenerator->setRequest($request);
    $result = $this->linkGenerator->generate('Test', 'test_route_1', array(), array('set_active_class' => TRUE));
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array('data-drupal-link-system-path' => 'test-route-1'),
    ), $result);

    // Render a link with the same path as the current path.
    $request = new Request(array(), array(), array('system_path' => 'test-route-1', RouteObjectInterface::ROUTE_NAME => 'test_route_1'));
    // This attribute is expected to be set in a Drupal request by
    // \Drupal\Core\ParamConverter\ParamConverterManager
    $raw_variables = new ParameterBag();
    $request->attributes->set('_raw_variables', $raw_variables);
    $this->linkGenerator->setRequest($request);
    $result = $this->linkGenerator->generate('Test', 'test_route_1', array(), array('set_active_class' => TRUE));
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array('data-drupal-link-system-path' => 'test-route-1'),
    ), $result);

    // Render a link with the same path as the current path, but with the
    // set_active_class option disabled.
    $request = new Request(array(), array(), array('system_path' => 'test-route-1', RouteObjectInterface::ROUTE_NAME => 'test_route_1'));
    // This attribute is expected to be set in a Drupal request by
    // \Drupal\Core\ParamConverter\ParamConverterManager
    $raw_variables = new ParameterBag();
    $request->attributes->set('_raw_variables', $raw_variables);
    $this->linkGenerator->setRequest($request);
    $result = $this->linkGenerator->generate('Test', 'test_route_1', array(), array('set_active_class' => FALSE));
    $this->assertNotTag(array(
      'tag' => 'a',
      'attributes' => array('data-drupal-link-system-path' => 'test-route-1'),
    ), $result);

    // Render a link with the same path and language as the current path.
    $result = $this->linkGenerator->generate('Test', 'test_route_1', array(), array('set_active_class' => TRUE));
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array('data-drupal-link-system-path' => 'test-route-1'),
    ), $result);

    // Render a link with the same path but a different language than the current
    // path.
    $result = $this->linkGenerator->generate(
      'Test',
      'test_route_1',
      array(),
      array(
        'language' => new Language(array('id' => 'de')),
        'set_active_class' => TRUE,
      )
    );
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array(
        'data-drupal-link-system-path' => 'test-route-1',
        'hreflang' => 'de',
      ),
    ), $result);

    // Render a link with the same path and query parameter as the current path.
    $request = new Request(array('value' => 'example_1'), array(), array('system_path' => 'test-route-3', RouteObjectInterface::ROUTE_NAME => 'test_route_3'));
    $raw_variables = new ParameterBag();
    $request->attributes->set('_raw_variables', $raw_variables);
    $this->linkGenerator->setRequest($request);
    $result = $this->linkGenerator->generate(
      'Test',
      'test_route_3',
      array(),
      array(
        'query' => array('value' => 'example_1'),
        'set_active_class' => TRUE,
      )
    );
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array(
        'data-drupal-link-system-path' => 'test-route-3',
        'data-drupal-link-query' => 'regexp:/.*value.*example_1.*/',
      ),
    ), $result);

    // Render a link with the same path but a different query parameter than the
    // current path.
    $result = $this->linkGenerator->generate(
      'Test',
      'test_route_3',
      array(),
      array(
        'query' => array('value' => 'example_2'),
        'set_active_class' => TRUE,
      )
    );
    $this->assertTag(array(
      'tag' => 'a',
      'attributes' => array(
        'data-drupal-link-system-path' => 'test-route-3',
        'data-drupal-link-query' => 'regexp:/.*value.*example_2.*/',
      ),
    ), $result);

    // Render a link with the same path and query parameter as the current path.
    $request = new Request(array('value' => 'example_1'), array(), array('system_path' => 'test-route-4/1', RouteObjectInterface::ROUTE_NAME => 'test_route_4'));
    $raw_variables = new ParameterBag(array('object' => '1'));
    $request->attributes->set('_raw_variables', $raw_variables);
    $this->linkGenerator->setRequest($request);
    $result = $this->linkGenerator->generate(
      'Test',
      'test_route_4',
      array('object' => '1'),
      array(
        'query' => array('value' => 'example_1'),
        'set_active_class' => TRUE,
      )
    );
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
