<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Form\FormAjaxResponseBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tests Drupal\Core\Form\FormAjaxResponseBuilder.
 */
#[CoversClass(FormAjaxResponseBuilder::class)]
#[Group('Form')]
class FormAjaxResponseBuilderTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Render\MainContent\MainContentRendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Form\FormAjaxResponseBuilder
   */
  protected $formAjaxResponseBuilder;

  /**
   * The callable resolver.
   */
  protected CallableResolver | Stub $callableResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->renderer = $this->createMock('Drupal\Core\Render\MainContent\MainContentRendererInterface');
    $this->routeMatch = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $this->callableResolver = $this->createStub(CallableResolver::class);
    $this->formAjaxResponseBuilder = new FormAjaxResponseBuilder($this->renderer, $this->routeMatch, $this->callableResolver);
  }

  /**
   * Tests build response no triggering element.
   *
   * @legacy-covers ::buildResponse
   */
  public function testBuildResponseNoTriggeringElement(): void {
    $this->renderer->expects($this->never())
      ->method('renderResponse');

    $request = new Request();
    $form = [];
    $form_state = new FormState();
    $commands = [];

    $this->expectException(HttpException::class);
    $this->formAjaxResponseBuilder->buildResponse($request, $form, $form_state, $commands);
  }

  /**
   * Tests build response no callable.
   *
   * @legacy-covers ::buildResponse
   */
  public function testBuildResponseNoCallable(): void {
    $this->renderer->expects($this->never())
      ->method('renderResponse');

    $request = new Request();
    $form = [];
    $form_state = new FormState();
    $triggering_element = [];
    $form_state->setTriggeringElement($triggering_element);
    $commands = [];

    $this->callableResolver->method('getCallableFromDefinition')->willThrowException(new \InvalidArgumentException());

    $this->expectException(HttpException::class);
    $this->formAjaxResponseBuilder->buildResponse($request, $form, $form_state, $commands);
  }

  /**
   * Tests build response render array.
   *
   * @legacy-covers ::buildResponse
   */
  public function testBuildResponseRenderArray(): void {
    $triggering_element = [
      '#ajax' => [
        'callback' => function (array $form, FormStateInterface $form_state) {
          return $form['test'];
        },
      ],
    ];
    $request = new Request();
    $form = [
      'test' => [
        '#type' => 'textfield',
      ],
    ];
    $form_state = new FormState();
    $form_state->setTriggeringElement($triggering_element);
    $commands = [];

    $this->callableResolver->method('getCallableFromDefinition')
      ->willReturn($triggering_element['#ajax']['callback']);

    $this->renderer->expects($this->once())
      ->method('renderResponse')
      ->with($form['test'], $request, $this->routeMatch)
      ->willReturn(new AjaxResponse([]));

    $result = $this->formAjaxResponseBuilder->buildResponse($request, $form, $form_state, $commands);
    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $result);
    $this->assertSame($commands, $result->getCommands());
  }

  /**
   * Tests build response response.
   *
   * @legacy-covers ::buildResponse
   */
  public function testBuildResponseResponse(): void {
    $triggering_element = [
      '#ajax' => [
        'callback' => function (array $form, FormStateInterface $form_state) {
          return new AjaxResponse([]);
        },
      ],
    ];
    $request = new Request();
    $form = [];
    $form_state = new FormState();
    $form_state->setTriggeringElement($triggering_element);
    $commands = [];

    $this->renderer->expects($this->never())
      ->method('renderResponse');

    $this->callableResolver->method('getCallableFromDefinition')
      ->willReturn($triggering_element['#ajax']['callback']);

    $result = $this->formAjaxResponseBuilder->buildResponse($request, $form, $form_state, $commands);
    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $result);
    $this->assertSame($commands, $result->getCommands());
  }

  /**
   * Tests build response with commands.
   *
   * @legacy-covers ::buildResponse
   */
  public function testBuildResponseWithCommands(): void {
    $triggering_element = [
      '#ajax' => [
        'callback' => function (array $form, FormStateInterface $form_state) {
          return new AjaxResponse([]);
        },
      ],
    ];
    $request = new Request();
    $form = [
      'test' => [
        '#type' => 'textfield',
      ],
    ];
    $form_state = new FormState();
    $form_state->setTriggeringElement($triggering_element);
    $commands = [
      new AlertCommand('alert!'),
    ];
    $commands_expected = [];
    $commands_expected[] = ['command' => 'alert', 'text' => 'alert!'];

    $this->renderer->expects($this->never())
      ->method('renderResponse');

    $this->callableResolver->method('getCallableFromDefinition')
      ->willReturn($triggering_element['#ajax']['callback']);

    $result = $this->formAjaxResponseBuilder->buildResponse($request, $form, $form_state, $commands);
    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $result);
    $this->assertSame($commands_expected, $result->getCommands());
  }

  /**
   * Tests build response with update command.
   *
   * @legacy-covers ::buildResponse
   */
  public function testBuildResponseWithUpdateCommand(): void {
    $triggering_element = [
      '#ajax' => [
        'callback' => function (array $form, FormStateInterface $form_state) {
          return new AjaxResponse([]);
        },
      ],
    ];
    $request = new Request();
    $form = [
      '#build_id' => 'the_build_id',
      '#build_id_old' => 'a_new_build_id',
      'test' => [
        '#type' => 'textfield',
      ],
    ];
    $form_state = new FormState();
    $form_state->setTriggeringElement($triggering_element);
    $commands = [
      new AlertCommand('alert!'),
    ];
    $commands_expected = [];
    $commands_expected[] = ['command' => 'update_build_id', 'old' => 'a_new_build_id', 'new' => 'the_build_id'];
    $commands_expected[] = ['command' => 'alert', 'text' => 'alert!'];

    $this->renderer->expects($this->never())
      ->method('renderResponse');

    $this->callableResolver->method('getCallableFromDefinition')
      ->willReturn($triggering_element['#ajax']['callback']);

    $result = $this->formAjaxResponseBuilder->buildResponse($request, $form, $form_state, $commands);
    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $result);
    $this->assertSame($commands_expected, $result->getCommands());
  }

}
