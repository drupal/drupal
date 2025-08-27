<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\field_ui\FieldUI;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\field_ui\FieldUI.
 */
#[CoversClass(FieldUI::class)]
#[Group('field_ui')]
class FieldUiTest extends UnitTestCase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pathValidator = $this->createMock('Drupal\Core\Path\PathValidatorInterface');
    $container = new ContainerBuilder();
    $container->set('path.validator', $this->pathValidator);
    \Drupal::setContainer($container);
  }

  /**
   * Tests get next destination.
   *
   * @legacy-covers ::getNextDestination
   */
  public function testGetNextDestination(): void {
    $destinations = ['admin', 'admin/content'];
    $expected_uri = 'base:admin';
    $expected_query = [
      'destinations' => ['admin/content'],
    ];
    $actual = FieldUI::getNextDestination($destinations);
    $this->assertSame($expected_uri, $actual->getUri());
    $this->assertSame($expected_query, $actual->getOption('query'));
  }

  /**
   * Tests get next destination empty.
   *
   * @legacy-covers ::getNextDestination
   */
  public function testGetNextDestinationEmpty(): void {
    $destinations = [];
    $actual = FieldUI::getNextDestination($destinations);
    $this->assertNull($actual);
  }

  /**
   * Tests get next destination route name.
   *
   * @legacy-covers ::getNextDestination
   */
  public function testGetNextDestinationRouteName(): void {
    $destinations = [['route_name' => 'system.admin'], ['route_name' => 'system.admin_content']];
    $expected_route_name = 'system.admin';
    $expected_query = [
      'destinations' => [['route_name' => 'system.admin_content']],
    ];
    $actual = FieldUI::getNextDestination($destinations);
    $this->assertSame($expected_route_name, $actual->getRouteName());
    $this->assertSame($expected_query, $actual->getOption('query'));
  }

}
