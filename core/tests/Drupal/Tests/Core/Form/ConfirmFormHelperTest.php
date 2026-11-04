<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Drupal\Core\Form\ConfirmFormHelper.
 */
#[CoversClass(ConfirmFormHelper::class)]
#[Group('Form')]
class ConfirmFormHelperTest extends UnitTestCase {

  /**
   * Tests the cancel link title.
   *
   * @see ::buildCancelLink
   */
  public function testCancelLinkTitle(): void {
    $cancel_text = 'Cancel text';
    $form = $this->createStub(ConfirmFormInterface::class);
    $form
      ->method('getCancelText')
      ->willReturn($cancel_text);

    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertSame($cancel_text, $link['#title']);
    $this->assertSame(['contexts' => ['url.query_args:destination']], $link['#cache']);
  }

  /**
   * Tests a cancel link route.
   *
   * @see ::buildCancelLink
   */
  public function testCancelLinkRoute(): void {
    $route_name = 'foo_bar';
    $cancel_route = new Url($route_name);
    $form = $this->createStub(ConfirmFormInterface::class);
    $form
      ->method('getCancelUrl')
      ->willReturn($cancel_route);
    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertEquals(Url::fromRoute($route_name), $link['#url']);
    $this->assertSame(['contexts' => ['url.query_args:destination']], $link['#cache']);
  }

  /**
   * Tests a cancel link route with parameters.
   *
   * @see ::buildCancelLink
   */
  public function testCancelLinkRouteWithParams(): void {
    $expected = Url::fromRoute('foo_bar.baz', ['baz' => 'banana'], ['absolute' => TRUE]);
    $form = $this->createStub(ConfirmFormInterface::class);
    $form
      ->method('getCancelUrl')
      ->willReturn($expected);
    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertEquals($expected, $link['#url']);
    $this->assertSame(['contexts' => ['url.query_args:destination']], $link['#cache']);
  }

  /**
   * Tests a cancel link route with a URL object.
   *
   * @see ::buildCancelLink
   */
  public function testCancelLinkRouteWithUrl(): void {
    $cancel_route = new Url(
      'foo_bar.baz', [
        'baz' => 'banana',
      ],
      [
        'absolute' => TRUE,
      ]
    );
    $form = $this->createStub(ConfirmFormInterface::class);
    $form
      ->method('getCancelUrl')
      ->willReturn($cancel_route);
    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertSame($cancel_route, $link['#url']);
    $this->assertSame(['contexts' => ['url.query_args:destination']], $link['#cache']);
  }

  /**
   * Tests a cancel link provided by the destination.
   *
   * @see ::buildCancelLink
   */
  #[DataProvider('providerTestCancelLinkDestination')]
  public function testCancelLinkDestination($destination): void {
    $query = ['destination' => $destination];
    $form = $this->createStub(ConfirmFormInterface::class);

    $path_validator = $this->createMock('Drupal\Core\Path\PathValidatorInterface');
    $container_builder = new ContainerBuilder();
    $container_builder->set('path.validator', $path_validator);
    \Drupal::setContainer($container_builder);

    $url = Url::fromRoute('test_route');
    $path_validator->expects($this->atLeastOnce())
      ->method('getUrlIfValidWithoutAccessCheck')
      ->with('baz')
      ->willReturn($url);

    $link = ConfirmFormHelper::buildCancelLink($form, new Request($query));
    $this->assertSame($url, $link['#url']);
    $this->assertSame(['contexts' => ['url.query_args:destination']], $link['#cache']);
  }

  /**
   * Provides test data for testCancelLinkDestination().
   */
  public static function providerTestCancelLinkDestination(): array {
    $data = [];
    $data[] = ['baz'];
    $data[] = ['/baz'];
    return $data;
  }

}
