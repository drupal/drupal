<?php

namespace Drupal\Tests\Core\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\Ajax\AjaxResponse
 * @group Ajax
 */
class AjaxResponseTest extends UnitTestCase {

  /**
   * The tested ajax response object.
   *
   * @var \Drupal\Core\Ajax\AjaxResponse
   */
  protected $ajaxResponse;

  protected function setUp() {
    $this->ajaxResponse = new AjaxResponse();
  }

  /**
   * Tests the add and getCommands method.
   *
   * @see \Drupal\Core\Ajax\AjaxResponse::addCommand()
   * @see \Drupal\Core\Ajax\AjaxResponse::getCommands()
   */
  public function testCommands() {
    $command_one = $this->createMock('Drupal\Core\Ajax\CommandInterface');
    $command_one->expects($this->once())
      ->method('render')
      ->will($this->returnValue(['command' => 'one']));
    $command_two = $this->createMock('Drupal\Core\Ajax\CommandInterface');
    $command_two->expects($this->once())
      ->method('render')
      ->will($this->returnValue(['command' => 'two']));
    $command_three = $this->createMock('Drupal\Core\Ajax\CommandInterface');
    $command_three->expects($this->once())
      ->method('render')
      ->will($this->returnValue(['command' => 'three']));

    $this->ajaxResponse->addCommand($command_one);
    $this->ajaxResponse->addCommand($command_two);
    $this->ajaxResponse->addCommand($command_three, TRUE);

    // Ensure that the added commands are in the right order.
    $commands =& $this->ajaxResponse->getCommands();
    $this->assertSame(['command' => 'one'], $commands[1]);
    $this->assertSame(['command' => 'two'], $commands[2]);
    $this->assertSame(['command' => 'three'], $commands[0]);

    // Remove one and change one element from commands and ensure the reference
    // worked as expected.
    unset($commands[2]);
    $commands[0]['class'] = 'test-class';

    $commands = $this->ajaxResponse->getCommands();
    $this->assertSame(['command' => 'one'], $commands[1]);
    $this->assertFalse(isset($commands[2]));
    $this->assertSame(['command' => 'three', 'class' => 'test-class'], $commands[0]);
  }

  /**
   * Tests the support for IE specific headers in file uploads.
   *
   * @cover ::prepareResponse
   */
  public function testPrepareResponseForIeFormRequestsWithFileUpload() {
    $request = Request::create('/example', 'POST');
    $request->headers->set('Accept', 'text/html');
    $response = new AjaxResponse([]);
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');

    $ajax_response_attachments_processor = $this->createMock('\Drupal\Core\Render\AttachmentsResponseProcessorInterface');
    $subscriber = new AjaxResponseSubscriber($ajax_response_attachments_processor);
    $event = new FilterResponseEvent(
      $this->createMock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      $response
    );
    $subscriber->onResponse($event);
    $this->assertEquals('text/html; charset=utf-8', $response->headers->get('Content-Type'));
    $this->assertEquals($response->getContent(), '<textarea>[]</textarea>');
  }

}
