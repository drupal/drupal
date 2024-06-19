<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form\EventSubscriber;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\EventSubscriber\FormAjaxSubscriber;
use Drupal\Core\Form\Exception\BrokenPostRequestException;
use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\Form\EventSubscriber\FormAjaxSubscriber
 * @group EventSubscriber
 */
class FormAjaxSubscriberTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Form\EventSubscriber\FormAjaxSubscriber
   */
  protected $subscriber;

  /**
   * @var \Drupal\Core\Form\FormAjaxResponseBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formAjaxResponseBuilder;

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpKernel;

  /**
   * The mocked string translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The mocked messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * The event used to derive the response.
   *
   * @var \Symfony\Component\HttpKernel\Event\ExceptionEvent
   */
  protected $event = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpKernel = $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $this->formAjaxResponseBuilder = $this->createMock('Drupal\Core\Form\FormAjaxResponseBuilderInterface');
    $this->stringTranslation = $this->getStringTranslationStub();
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->subscriber = new FormAjaxSubscriber($this->formAjaxResponseBuilder, $this->stringTranslation, $this->messenger);
  }

  /**
   * @covers ::onException
   */
  public function testOnException(): void {
    $form = ['#type' => 'form', '#build_id' => 'the_build_id'];
    $expected_form = $form + [
      '#build_id_old' => 'the_build_id',
    ];
    $form_state = new FormState();
    $exception = new FormAjaxException($form, $form_state);

    $request = new Request([], ['form_build_id' => 'the_build_id']);
    $commands = [];
    $response = new Response('');

    $this->formAjaxResponseBuilder->expects($this->once())
      ->method('buildResponse')
      ->with($request, $expected_form, $form_state, $commands)
      ->willReturn($response);

    $this->assertResponseFromException($request, $exception, $response);
    $this->assertTrue($this->event->isAllowingCustomResponseCode());
    $this->assertSame(200, $this->event->getResponse()->getStatusCode());
  }

  /**
   * @covers ::onException
   */
  public function testOnExceptionNewBuildId(): void {
    $form = ['#type' => 'form', '#build_id' => 'the_build_id'];
    $expected_form = $form + [
      '#build_id_old' => 'a_new_build_id',
    ];
    $form_state = new FormState();
    $exception = new FormAjaxException($form, $form_state);

    $request = new Request([], ['form_build_id' => 'a_new_build_id']);
    $commands = [];
    $response = new Response('');

    $this->formAjaxResponseBuilder->expects($this->once())
      ->method('buildResponse')
      ->with($request, $expected_form, $form_state, $commands)
      ->willReturn($response);

    $this->assertResponseFromException($request, $exception, $response);
    $this->assertTrue($this->event->isAllowingCustomResponseCode());
    $this->assertSame(200, $this->event->getResponse()->getStatusCode());
  }

  /**
   * @covers ::onException
   */
  public function testOnExceptionOtherClass(): void {
    $request = new Request();
    $exception = new \Exception();

    $this->formAjaxResponseBuilder->expects($this->never())
      ->method('buildResponse');

    $this->assertResponseFromException($request, $exception, NULL);
  }

  /**
   * @covers ::onException
   */
  public function testOnExceptionResponseBuilderException(): void {
    $form = ['#type' => 'form', '#build_id' => 'the_build_id'];
    $expected_form = $form + [
      '#build_id_old' => 'the_build_id',
    ];
    $form_state = new FormState();
    $exception = new FormAjaxException($form, $form_state);
    $request = new Request([], ['form_build_id' => 'the_build_id']);
    $commands = [];

    $expected_exception = new HttpException(500, 'The specified #ajax callback is empty or not callable.');
    $this->formAjaxResponseBuilder->expects($this->once())
      ->method('buildResponse')
      ->with($request, $expected_form, $form_state, $commands)
      ->willThrowException($expected_exception);

    $this->assertResponseFromException($request, $exception, NULL);
    $this->assertSame($expected_exception, $this->event->getThrowable());
  }

  /**
   * @covers ::onException
   */
  public function testOnExceptionBrokenPostRequest(): void {
    $this->formAjaxResponseBuilder->expects($this->never())
      ->method('buildResponse');

    $this->messenger->expects($this->once())
      ->method('addError');

    $this->subscriber = new FormAjaxSubscriber($this->formAjaxResponseBuilder, $this->getStringTranslationStub(), $this->messenger);

    $rendered_output = 'the rendered output';
    // CommandWithAttachedAssetsTrait::getRenderedContent() will call the
    // renderer service via the container.
    $renderer = $this->createMock('Drupal\Core\Render\RendererInterface');
    $renderer->expects($this->once())
      ->method('renderRoot')
      ->with()
      ->willReturnCallback(function (&$elements) use ($rendered_output) {
        $elements['#attached'] = [];
        return $rendered_output;
      });
    $container = new ContainerBuilder();
    $container->set('renderer', $renderer);
    \Drupal::setContainer($container);

    $exception = new BrokenPostRequestException((int) (32 * 1e6));
    $request = new Request([FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]);

    $event = new ExceptionEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    $this->subscriber->onException($event);
    $this->assertTrue($event->isAllowingCustomResponseCode());
    $actual_response = $event->getResponse();
    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $actual_response);
    $this->assertSame(200, $actual_response->getStatusCode());
    $expected_commands[] = [
      'command' => 'insert',
      'method' => 'prepend',
      'selector' => NULL,
      'data' => $rendered_output,
      'settings' => NULL,
    ];
    $this->assertSame($expected_commands, $actual_response->getCommands());
  }

  /**
   * @covers ::onException
   * @covers ::getFormAjaxException
   */
  public function testOnExceptionNestedException(): void {
    $form = ['#type' => 'form', '#build_id' => 'the_build_id'];
    $expected_form = $form + [
      '#build_id_old' => 'the_build_id',
    ];
    $form_state = new FormState();
    $form_exception = new FormAjaxException($form, $form_state);
    $exception = new \Exception('', 0, $form_exception);

    $request = new Request([], ['form_build_id' => 'the_build_id']);
    $commands = [];
    $response = new Response('');

    $this->formAjaxResponseBuilder->expects($this->once())
      ->method('buildResponse')
      ->with($request, $expected_form, $form_state, $commands)
      ->willReturn($response);

    $this->assertResponseFromException($request, $exception, $response);
  }

  /**
   * @covers ::getFormAjaxException
   */
  public function testOnExceptionNestedWrongException(): void {
    $nested_exception = new \Exception();
    $exception = new \Exception('', 0, $nested_exception);
    $request = new Request();

    $this->formAjaxResponseBuilder->expects($this->never())
      ->method('buildResponse');

    $this->assertResponseFromException($request, $exception, NULL);
  }

  /**
   * Asserts that the expected response is derived from the given exception.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Exception $exception
   *   The exception to pass to the event.
   * @param \Symfony\Component\HttpFoundation\Response|null $expected_response
   *   The response expected to be set on the event.
   *
   * @internal
   */
  protected function assertResponseFromException(Request $request, \Exception $exception, ?Response $expected_response): void {
    $this->event = new ExceptionEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    $this->subscriber->onException($this->event);

    $this->assertSame($expected_response, $this->event->getResponse());
  }

}
