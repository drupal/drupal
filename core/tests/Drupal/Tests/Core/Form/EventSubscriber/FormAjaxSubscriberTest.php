<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\EventSubscriber\FormAjaxSubscriberTest.
 */

namespace Drupal\Tests\Core\Form\EventSubscriber;

use Drupal\Core\Form\EventSubscriber\FormAjaxSubscriber;
use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
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
   * @var \Drupal\Core\Form\FormAjaxResponseBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formAjaxResponseBuilder;

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $httpKernel;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->httpKernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $this->formAjaxResponseBuilder = $this->getMock('Drupal\Core\Form\FormAjaxResponseBuilderInterface');
    $this->subscriber = new FormAjaxSubscriber($this->formAjaxResponseBuilder);
  }

  /**
   * @covers ::onException
   */
  public function testOnException() {
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

    $event = $this->assertResponseFromException($request, $exception, $response);
    $this->assertSame(200, $event->getResponse()->headers->get('X-Status-Code'));
  }

  /**
   * @covers ::onException
   */
  public function testOnExceptionNewBuildId() {
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

    $event = $this->assertResponseFromException($request, $exception, $response);
    $this->assertSame(200, $event->getResponse()->headers->get('X-Status-Code'));
  }

  /**
   * @covers ::onException
   */
  public function testOnExceptionOtherClass() {
    $request = new Request();
    $exception = new \Exception();

    $this->formAjaxResponseBuilder->expects($this->never())
      ->method('buildResponse');

    $this->assertResponseFromException($request, $exception, NULL);
  }

  /**
   * @covers ::onException
   */
  public function testOnExceptionResponseBuilderException() {
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

    $event = $this->assertResponseFromException($request, $exception, NULL);
    $this->assertSame($expected_exception, $event->getException());
  }

  /**
   * @covers ::onException
   * @covers ::getFormAjaxException
   */
  public function testOnExceptionNestedException() {
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
  public function testOnExceptionNestedWrongException() {
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
   * @return \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent
   *   The event used to derive the response.
   */
  protected function assertResponseFromException(Request $request, \Exception $exception, $expected_response) {
    $event = new GetResponseForExceptionEvent($this->httpKernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
    $this->subscriber->onException($event);

    $this->assertSame($expected_response, $event->getResponse());
    return $event;
  }

}
