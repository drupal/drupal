<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->ajaxResponse = new AjaxResponse();
  }

  /**
   * Tests the add and getCommands method.
   *
   * @see \Drupal\Core\Ajax\AjaxResponse::addCommand()
   * @see \Drupal\Core\Ajax\AjaxResponse::getCommands()
   */
  public function testCommands(): void {
    $command_one = $this->createMock('Drupal\Core\Ajax\CommandInterface');
    $command_one->expects($this->once())
      ->method('render')
      ->willReturn(['command' => 'one']);
    $command_two = $this->createMock('Drupal\Core\Ajax\CommandInterface');
    $command_two->expects($this->once())
      ->method('render')
      ->willReturn(['command' => 'two']);
    $command_three = $this->createMock('Drupal\Core\Ajax\CommandInterface');
    $command_three->expects($this->once())
      ->method('render')
      ->willReturn(['command' => 'three']);

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
  public function testPrepareResponseForIeFormRequestsWithFileUpload(): void {
    $request = Request::create('/example', 'POST');
    $request->headers->set('Accept', 'text/html');
    $response = new AjaxResponse([]);
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');

    $ajax_response_attachments_processor = $this->createMock('\Drupal\Core\Render\AttachmentsResponseProcessorInterface');
    $subscriber = new AjaxResponseSubscriber(fn() => $ajax_response_attachments_processor);
    $event = new ResponseEvent(
      $this->createMock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );
    $subscriber->onResponse($event);
    $this->assertEquals('text/html; charset=utf-8', $response->headers->get('Content-Type'));
    $this->assertEquals('<textarea>[]</textarea>', $response->getContent());
  }

  /**
   * Tests the mergeWith() method.
   *
   * @see \Drupal\Core\Ajax\AjaxResponse::mergeWith()
   *
   * @throws \PHPUnit\Framework\MockObject\Exception
   */
  public function testMergeWithOtherAjaxResponse(): void {
    $response = new AjaxResponse([]);

    $command_one = $this->createCommandMock('one');

    $command_two = $this->createCommandMockWithSettingsAndLibrariesAttachments(
      'Drupal\Core\Ajax\HtmlCommand', [
        'setting1' => 'value1',
        'setting2' => 'value2',
      ], ['jquery', 'drupal'], 'two');
    $command_three = $this->createCommandMockWithSettingsAndLibrariesAttachments(
      'Drupal\Core\Ajax\InsertCommand', [
        'setting1' => 'overridden',
        'setting3' => 'value3',
      ], ['jquery', 'ajax'], 'three');

    $response->addCommand($command_one);
    $response->addCommand($command_two);

    $response2 = new AjaxResponse([]);
    $response2->addCommand($command_three);

    $response->mergeWith($response2);
    self::assertEquals([
      'library' => ['jquery', 'drupal', 'jquery', 'ajax'],
      'drupalSettings' => [
        'setting1' => 'overridden',
        'setting2' => 'value2',
        'setting3' => 'value3',
      ],
    ], $response->getAttachments());
    self::assertEquals([['command' => 'one'], ['command' => 'two'], ['command' => 'three']], $response->getCommands());
  }

  /**
   * Creates a mock of a provided subclass of CommandInterface.
   *
   * Adds given settings and libraries to assets mock
   * that is attached to the command mock.
   *
   * @param string $command_class_name
   *   The command class name to create the mock for.
   * @param array|null $settings
   *   The settings to attach.
   * @param array|null $libraries
   *   The libraries to attach.
   * @param string $command_name
   *   The command name to pass to the mock.
   */
  private function createCommandMockWithSettingsAndLibrariesAttachments(
    string $command_class_name,
    array|null $settings,
    array|null $libraries,
    string $command_name,
  ): CommandInterface {
    $command = $this->createMock($command_class_name);
    $command->expects($this->once())
      ->method('render')
      ->willReturn(['command' => $command_name]);

    $assets = $this->createMock('Drupal\Core\Asset\AttachedAssetsInterface');
    $assets->expects($this->once())->method('getLibraries')->willReturn($libraries);
    $assets->expects($this->once())->method('getSettings')->willReturn($settings);

    $command->expects($this->once())->method('getAttachedAssets')->willReturn($assets);

    return $command;
  }

  /**
   * Creates a mock of the Drupal\Core\Ajax\CommandInterface.
   *
   * @param string $command_name
   *   The command name to pass to the mock.
   */
  private function createCommandMock(string $command_name): CommandInterface {
    $command = $this->createMock('Drupal\Core\Ajax\CommandInterface');
    $command->expects($this->once())
      ->method('render')
      ->willReturn(['command' => $command_name]);

    return $command;
  }

}
