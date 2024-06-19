<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Action;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the EmailAction plugin.
 *
 * @group action
 */
class EmailActionTest extends KernelTestBase {
  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('dblog', ['watchdog']);
  }

  /**
   * Tests the email action plugin.
   */
  public function testEmailAction(): void {
    $this->config('system.site')->set('mail', 'test@example.com')->save();

    /** @var \Drupal\Core\Action\ActionManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.action');
    $configuration = [
      'recipient' => 'test@example.com',
      'subject' => 'Test subject',
      'message' => 'Test message',
    ];
    $plugin_manager
      ->createInstance('action_send_email_action', $configuration)
      ->execute();

    $mails = $this->getMails();
    $this->assertCount(1, $this->getMails());
    $this->assertEquals('test@example.com', $mails[0]['to']);
    $this->assertEquals('Test subject', $mails[0]['subject']);
    $this->assertEquals("Test message\n", $mails[0]['body']);

    // Ensure that the email sending is logged.
    $log = \Drupal::database()
      ->select('watchdog', 'w')
      ->fields('w', ['message', 'variables'])
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetch();

    $this->assertEquals('Sent email to %recipient', $log->message);
    $variables = unserialize($log->variables);
    $this->assertEquals('test@example.com', $variables['%recipient']);
  }

}
