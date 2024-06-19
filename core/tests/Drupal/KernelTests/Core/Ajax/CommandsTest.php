<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Performs tests on AJAX framework commands.
 *
 * @group Ajax
 */
class CommandsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'ajax_test',
    'ajax_forms_test',
  ];

  /**
   * Regression test: Settings command exists regardless of JS aggregation.
   */
  public function testAttachedSettings(): void {
    $assert = function ($message) {
      $response = new AjaxResponse();
      $response->setAttachments([
        'library' => ['core/drupalSettings'],
        'drupalSettings' => ['foo' => 'bar'],
      ]);

      $ajax_response_attachments_processor = \Drupal::service('ajax_response.attachments_processor');
      $subscriber = new AjaxResponseSubscriber(fn() => $ajax_response_attachments_processor);
      $event = new ResponseEvent(
        \Drupal::service('http_kernel'),
        new Request(),
        HttpKernelInterface::MAIN_REQUEST,
        $response
      );
      $subscriber->onResponse($event);
      $expected = [
        'command' => 'settings',
      ];
      $this->assertCommand($response->getCommands(), $expected, $message);
    };

    $config = $this->config('system.performance');

    $config->set('js.preprocess', FALSE)->save();
    $assert('Settings command exists when JS aggregation is disabled.');

    $config->set('js.preprocess', TRUE)->save();
    $assert('Settings command exists when JS aggregation is enabled.');
  }

  /**
   * Checks empty content in commands does not throw exceptions.
   *
   * @doesNotPerformAssertions
   */
  public function testEmptyInsertCommand(): void {
    (new InsertCommand('foobar', []))->render();
  }

  /**
   * Asserts the array of Ajax commands contains the searched command.
   *
   * An AjaxResponse object stores an array of Ajax commands. This array
   * sometimes includes commands automatically provided by the framework in
   * addition to commands returned by a particular controller. During testing,
   * we're usually interested that a particular command is present, and don't
   * care whether other commands precede or follow the one we're interested in.
   * Additionally, the command we're interested in may include additional data
   * that we're not interested in. Therefore, this function simply asserts that
   * one of the commands in $haystack contains all of the keys and values in
   * $needle. Furthermore, if $needle contains a 'settings' key with an array
   * value, we simply assert that all keys and values within that array are
   * present in the command we're checking, and do not consider it a failure if
   * the actual command contains additional settings that aren't part of
   * $needle.
   *
   * @param array $haystack
   *   An array of rendered Ajax commands returned by the server.
   * @param array $needle
   *   Array of info we're expecting in one of those commands.
   * @param string $message
   *   An assertion message.
   *
   * @internal
   */
  protected function assertCommand(array $haystack, array $needle, string $message): void {
    $found = FALSE;
    foreach ($haystack as $command) {
      // If the command has additional settings that we're not testing for, do
      // not consider that a failure.
      if (isset($command['settings']) && is_array($command['settings']) && isset($needle['settings']) && is_array($needle['settings'])) {
        $command['settings'] = array_intersect_key($command['settings'], $needle['settings']);
      }
      // If the command has additional data that we're not testing for, do not
      // consider that a failure. Also, == instead of ===, because we don't
      // require the key/value pairs to be in any particular order
      // (http://php.net/manual/language.operators.array.php).
      if (array_intersect_key($command, $needle) == $needle) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found, $message);
  }

}
