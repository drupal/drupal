<?php

namespace Drupal\Tests\help_topics\Unit;

use Drupal\Core\Cache\Cache;
use Drupal\help_topics\HelpTopicTwig;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for the HelpTopicTwig class.
 *
 * Note that the toUrl() and toLink() methods are not covered, because they
 * have calls to new Url() and new Link() in them, so they cannot be unit
 * tested.
 *
 * @coversDefaultClass \Drupal\help_topics\HelpTopicTwig
 * @group help_topics
 */
class HelpTopicTwigTest extends UnitTestCase {

  /**
   * The help topic instance to test.
   *
   * @var \Drupal\help_topics\HelpTopicTwig
   */
  protected $helpTopic;

  /**
   * The plugin information to use for setting up a test topic.
   *
   * @var array
   */
  const PLUGIN_INFORMATION = [
    'id' => 'test.topic',
    'provider' => 'test',
    'label' => 'This is the topic label',
    'top_level' => TRUE,
    'related' => ['something'],
    'body' => '<p>This is the topic body</p>',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->helpTopic = new HelpTopicTwig([],
      self::PLUGIN_INFORMATION['id'],
      self::PLUGIN_INFORMATION,
      $this->getTwigMock());
  }

  /**
   * @covers ::getBody
   * @covers ::getLabel
   */
  public function testText() {
    $this->assertEquals($this->helpTopic->getBody(),
      ['#markup' => self::PLUGIN_INFORMATION['body']]);
    $this->assertEquals($this->helpTopic->getLabel(),
      self::PLUGIN_INFORMATION['label']);
  }

  /**
   * @covers ::getProvider
   * @covers ::isTopLevel
   * @covers ::getRelated
   */
  public function testDefinition() {
    $this->assertEquals($this->helpTopic->getProvider(),
      self::PLUGIN_INFORMATION['provider']);
    $this->assertEquals($this->helpTopic->isTopLevel(),
      self::PLUGIN_INFORMATION['top_level']);
    $this->assertEquals($this->helpTopic->getRelated(),
      self::PLUGIN_INFORMATION['related']);
  }

  /**
   * @covers ::getCacheContexts
   * @covers ::getCacheTags
   * @covers ::getCacheMaxAge
   */
  public function testCacheInfo() {
    $this->assertEquals($this->helpTopic->getCacheContexts(), []);
    $this->assertEquals($this->helpTopic->getCacheTags(), ['core.extension']);
    $this->assertEquals($this->helpTopic->getCacheMaxAge(), Cache::PERMANENT);
  }

  /**
   * Creates a mock Twig loader class for the test.
   */
  protected function getTwigMock() {
    $twig = $this
      ->getMockBuilder('Drupal\Core\Template\TwigEnvironment')
      ->disableOriginalConstructor()
      ->getMock();

    $twig
      ->method('load')
      ->willReturn(new FakeTemplateWrapper(self::PLUGIN_INFORMATION['body']));

    return $twig;
  }

}

/**
 * Defines a fake template class to mock \Twig\TemplateWrapper.
 *
 * We cannot use getMockBuilder() for this, because the Twig TemplateWrapper
 * class is declared "final" and cannot be mocked.
 */
class FakeTemplateWrapper {

  /**
   * Body text to return from the render() method.
   *
   * @var string
   */
  protected $body;

  /**
   * Constructor.
   *
   * @param string $body
   *   Body text to return from the render() method.
   */
  public function __construct($body) {
    $this->body = $body;
  }

  /**
   * Mocks the \Twig\TemplateWrapper render() method.
   *
   * @param array $context
   *   (optional) Render context.
   */
  public function render(array $context = []) {
    return $this->body;
  }

}
