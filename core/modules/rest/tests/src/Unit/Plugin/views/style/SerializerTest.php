<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Unit\Plugin\views\style;

use Drupal\rest\Plugin\views\display\RestExport;
use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Tests Drupal\rest\Plugin\views\style\Serializer.
 */
#[CoversClass(Serializer::class)]
#[Group('rest')]
class SerializerTest extends UnitTestCase {

  /**
   * The View instance.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\Stub
   */
  protected $view;

  /**
   * The RestExport display handler.
   *
   * @var \Drupal\rest\Plugin\views\display\RestExport|\PHPUnit\Framework\MockObject\Stub
   */
  protected $displayHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->view = $this->createStub(ViewExecutable::class);

    // Make the view result empty so we don't have to mock the row plugin render
    // call.
    $this->view->result = [];

    $this->displayHandler = $this->createStub(RestExport::class);
    $this->displayHandler
      ->method('getContentType')
      ->willReturn('json');
  }

  /**
   * Tests that the symfony serializer receives style plugin from the render() method.
   *
   * @see ::render
   */
  public function testSerializerReceivesOptions(): void {
    $mock_serializer = $this->prophesize(SerializerInterface::class);

    // This is the main expectation of the test. We want to make sure the
    // serializer options are passed to the SerializerInterface object.
    $mock_serializer->serialize([], 'json', Argument::that(function (array $argument): bool {
      return isset($argument['views_style_plugin'])
        && $argument['views_style_plugin'] instanceof Serializer;
    }))
      ->willReturn('')
      ->shouldBeCalled();

    $view_serializer_style = new Serializer(
      [],
      'dummy_serializer',
      [],
      $mock_serializer->reveal(),
      ['json', 'xml'],
      ['json' => 'serialization', 'xml' => 'serialization']);
    $view_serializer_style->options = ['formats' => ['xml', 'json']];
    $view_serializer_style->view = $this->view;
    $view_serializer_style->displayHandler = $this->displayHandler;
    $view_serializer_style->render();
  }

}
