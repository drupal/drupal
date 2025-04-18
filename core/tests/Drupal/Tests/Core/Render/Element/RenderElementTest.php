<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\RenderElementBase
 * @group Render
 */
class RenderElementTest extends UnitTestCase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->requestStack = new RequestStack();
    $this->container = new ContainerBuilder();
    $this->container->set('request_stack', $this->requestStack);
    \Drupal::setContainer($this->container);
  }

  /**
   * @covers ::preRenderAjaxForm
   */
  public function testPreRenderAjaxForm(): void {
    $request = Request::create('/test');
    $request->query->set('foo', 'bar');
    $this->requestStack->push($request);

    $prophecy = $this->prophesize('Drupal\Core\Routing\UrlGeneratorInterface');
    $url = '/test?foo=bar&ajax_form=1';
    $prophecy->generateFromRoute('<current>', [], ['query' => ['foo' => 'bar', FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]], TRUE)
      ->willReturn((new GeneratedUrl())->setCacheContexts(['route'])->setGeneratedUrl($url));

    $url_generator = $prophecy->reveal();
    $this->container->set('url_generator', $url_generator);

    $element = [
      '#type' => 'select',
      '#id' => 'test',
      '#ajax' => [
        'wrapper' => 'foo',
        'callback' => 'test-callback',
      ],
    ];

    $element = RenderElementBase::preRenderAjaxForm($element);

    $this->assertTrue($element['#ajax_processed']);
    $this->assertEquals($url, $element['#attached']['drupalSettings']['ajax']['test']['url']);
  }

  /**
   * @covers ::preRenderAjaxForm
   */
  public function testPreRenderAjaxFormWithQueryOptions(): void {
    $request = Request::create('/test');
    $request->query->set('foo', 'bar');
    $this->requestStack->push($request);

    $prophecy = $this->prophesize('Drupal\Core\Routing\UrlGeneratorInterface');
    $url = '/test?foo=bar&other=query&ajax_form=1';
    $prophecy->generateFromRoute('<current>', [], ['query' => ['foo' => 'bar', 'other' => 'query', FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]], TRUE)
      ->willReturn((new GeneratedUrl())->setCacheContexts(['route'])->setGeneratedUrl($url));

    $url_generator = $prophecy->reveal();
    $this->container->set('url_generator', $url_generator);

    $element = [
      '#type' => 'select',
      '#id' => 'test',
      '#ajax' => [
        'wrapper' => 'foo',
        'callback' => 'test-callback',
        'options' => [
          'query' => [
            'other' => 'query',
          ],
        ],
      ],
    ];

    $element = RenderElementBase::preRenderAjaxForm($element);

    $this->assertTrue($element['#ajax_processed']);
    $this->assertEquals($url, $element['#attached']['drupalSettings']['ajax']['test']['url']);
  }

  /**
   * @covers ::setAttributes
   *
   * @dataProvider providerTestSetAttributes
   */
  public function testSetAttributes(array $element, array $class, array $expected): void {
    RenderElementBase::setAttributes($element, $class);
    $this->assertSame($expected, $element);
  }

  /**
   * Provides test data for testSetAttributes().
   */
  public static function providerTestSetAttributes(): array {
    return [
      'No-op' => [
        'element' => [
          '#type' => 'textfield',
        ],
        'class' => [],
        'expected' => [
          '#type' => 'textfield',
        ],
      ],
      'Add first class' => [
        'element' => [
          '#type' => 'textfield',
        ],
        'class' => ['foo', 'bar'],
        'expected' => [
          '#type' => 'textfield',
          '#attributes' => [
            'class' => [
              'foo',
              'bar',
            ],
          ],
        ],
      ],
      'Append classes' => [
        'element' => [
          '#type' => 'textfield',
          '#attributes' => [
            'class' => [
              'foo',
              'bar',
            ],
          ],
        ],
        'class' => ['baz'],
        'expected' => [
          '#type' => 'textfield',
          '#attributes' => [
            'class' => [
              'foo',
              'bar',
              'baz',
            ],
          ],
        ],
      ],
      'Required' => [
        'element' => [
          '#type' => 'textfield',
          '#required' => TRUE,
        ],
        'class' => [],
        'expected' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#attributes' => [
            'class' => [
              'required',
            ],
            'required' => 'required',
          ],
        ],
      ],
      'Parent with error' => [
        'element' => [
          '#type' => 'textfield',
          '#parents' => ['dummy_parent'],
          '#errors' => 'invalid',
          '#validated' => TRUE,
        ],
        'class' => [],
        'expected' => [
          '#type' => 'textfield',
          '#parents' => ['dummy_parent'],
          '#errors' => 'invalid',
          '#validated' => TRUE,
          '#attributes' => [
            'class' => [
              'error',
            ],
            'aria-invalid' => 'true',
          ],
        ],
      ],
    ];
  }

}
