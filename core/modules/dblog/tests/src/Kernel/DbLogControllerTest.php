<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Kernel;

use Drupal\dblog\Controller\DbLogController;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the DbLogController class.
 *
 * @group dblog
 */
class DbLogControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('dblog', ['watchdog']);
  }

  /**
   * Tests links with non latin characters.
   */
  public function testNonLatinCharacters(): void {

    $link = 'hello-
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰
      科州的小九寨沟绝美高山湖泊酱凉拌素鸡照烧鸡黄玫瑰';

    \Drupal::logger('my_module')->warning('test', ['link' => $link]);

    $log = \Drupal::database()
      ->select('watchdog', 'w')
      ->fields('w', ['link'])
      ->condition('link', '', '<>')
      ->execute()
      ->fetchField();

    $this->assertEquals($log, $link);
  }

  /**
   * Tests corrupted log entries can still display available data.
   */
  public function testDbLogCorrupted(): void {
    $dblog_controller = DbLogController::create($this->container);

    // Check message with properly serialized data.
    $message = (object) [
      'message' => 'Sample message with placeholder: @placeholder',
      'variables' => serialize(['@placeholder' => 'test placeholder']),
    ];

    $this->assertEquals('Sample message with placeholder: test placeholder', $dblog_controller->formatMessage($message));

    // Check that controller work with corrupted data.
    $message->variables = 'BAD SERIALIZED DATA';
    $formatted = $dblog_controller->formatMessage($message);
    $this->assertEquals('Log data is corrupted and cannot be unserialized: Sample message with placeholder: @placeholder', $formatted);
  }

}
