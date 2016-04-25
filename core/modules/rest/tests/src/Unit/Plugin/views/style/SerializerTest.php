<?php

namespace Drupal\Tests\rest\Unit\Plugin\views\style;

use Drupal\rest\Plugin\views\display\RestExport;
use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewExecutable;
use Prophecy\Argument;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @coversDefaultClass \Drupal\rest\Plugin\views\style\Serializer
 * @group rest
 */
class SerializerTest extends UnitTestCase {

  /**
   * The View instance.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $view;

  /**
   * The RestExport display handler.
   *
   * @var \Drupal\rest\Plugin\views\display\RestExport|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $displayHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->view = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Make the view result empty so we don't have to mock the row plugin render
    // call.
    $this->view->result = [];

    $this->displayHandler = $this->getMockBuilder(RestExport::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->displayHandler->expects($this->any())
      ->method('getContentType')
      ->willReturn('json');
  }

  /**
   * Tests that the symfony serializer receives style plugin from the render() method.
   *
   * @covers ::render
   */
  public function testSerializerReceivesOptions() {
    $mock_serializer = $this->prophesize(SerializerInterface::class);

    // This is the main expectation of the test. We want to make sure the
    // serializer options are passed to the SerializerInterface object.
    $mock_serializer->serialize([], 'json', Argument::that(function ($argument) {
      return isset($argument['views_style_plugin']) && $argument['views_style_plugin'] instanceof Serializer;
    }))
      ->willReturn()
      ->shouldBeCalled();

    $view_serializer_style = new Serializer([], 'dummy_serializer', [], $mock_serializer->reveal(), ['json', 'xml']);
    $view_serializer_style->options = ['formats' => ['xml', 'json']];
    $view_serializer_style->view = $this->view;
    $view_serializer_style->displayHandler = $this->displayHandler;
    $view_serializer_style->render();
  }

}
