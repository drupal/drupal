<?php

declare(strict_types=1);

namespace Drupal\Tests\syslog\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Test syslog logger functionality.
 *
 * @group syslog
 * @coversDefaultClass \Drupal\syslog\Logger\SysLog
 */
class SyslogTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['syslog', 'syslog_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['syslog']);
  }

  /**
   * @covers ::log
   */
  public function testSyslogWriting(): void {

    $request = Request::create('/page-not-found', 'GET', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
    $request->headers->set('Referer', 'other-site');
    $request->setSession(new Session(new MockArraySessionStorage()));
    \Drupal::requestStack()->push($request);

    $user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')->getMock();
    $user->method('id')->willReturn(42);
    $this->container->set('current_user', $user);

    \Drupal::logger('my_module')->warning('My warning message.', ['link' => '/my-link']);

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $logs = explode(PHP_EOL, file_get_contents($log_filename));
    $log = explode('|', $logs[0]);

    global $base_url;
    $this->assertEquals($base_url, $log[0]);
    $this->assertEquals('my_module', $log[2]);
    $this->assertEquals('1.2.3.4', $log[3]);
    $this->assertEquals($base_url . '/page-not-found', $log[4]);
    $this->assertEquals('other-site', $log[5]);
    $this->assertEquals('42', $log[6]);
    $this->assertEquals('/my-link', $log[7]);
    $this->assertEquals('My warning message.', $log[8]);

    // Test that an empty format prevents writing to the syslog.
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('syslog.settings');
    $config->set('format', '');
    $config->save();
    unlink($log_filename);
    \Drupal::logger('my_module')->warning('My warning message.', ['link' => '/my-link']);
    $this->assertFileDoesNotExist($log_filename);
  }

  /**
   * Tests that missing facility prevents writing to the syslog.
   *
   * @covers ::openConnection
   */
  public function testSyslogMissingFacility(): void {
    $config = $this->container->get('config.factory')->getEditable('syslog.settings');
    $config->clear('facility');
    $config->save();
    \Drupal::logger('my_module')->warning('My warning message.');
    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $this->assertFileDoesNotExist($log_filename);
  }

  /**
   * Tests severity level logging.
   *
   * @covers ::log
   */
  public function testSyslogSeverity(): void {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('syslog.settings');
    $config->set('format', '!type|!message|!severity');
    $config->save();

    \Drupal::logger('my_module')->warning('My warning message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $logs = explode(PHP_EOL, file_get_contents($log_filename));
    $log = explode('|', $logs[0]);

    $this->assertEquals('my_module', $log[0]);
    $this->assertEquals('My warning message.', $log[1]);
    $this->assertEquals('4', $log[2]);
  }

}
