<?php

namespace Drupal\Tests\simpletest\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\TestDiscovery;
use Drupal\simpletest\WebTestBase;

/**
 * Test the deprecation messages for Simpletest test hooks.
 *
 * @group simpletest
 * @group legacy
 */
class TestDeprecatedTestHooks extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['simpletest', 'simpletest_deprecation_test'];

  /**
   * @expectedDeprecation The deprecated hook hook_test_group_finished() is implemented in these functions: simpletest_deprecation_test_test_group_finished(). Convert your test to a PHPUnit-based one and implement test listeners. See https://www.drupal.org/node/2934242
   */
  public function testHookTestGroupFinished() {
    // @todo Mock the messenger service and add expectations when
    // \Drupal::messenger() actually uses the service.
    // @see https://www.drupal.org/node/2928994
    $this->assertNull(_simpletest_batch_finished(TRUE, [], [], 10));
  }

  /**
   * @expectedDeprecation The deprecated hook hook_test_group_started() is implemented in these functions: simpletest_deprecation_test_test_group_started(). Convert your test to a PHPUnit-based one and implement test listeners. See https://www.drupal.org/node/2934242
   */
  public function testHookTestGroupStarted() {
    // Mock a database connection enough for simpletest_run_tests().
    $insert = $this->getMockBuilder(Insert::class)
      ->disableOriginalConstructor()
      ->setMethods(['execute', 'useDefaults'])
      ->getMock();
    $insert->expects($this->any())
      ->method('useDefaults')
      ->willReturn($insert);
    $insert->expects($this->any())
      ->method('execute')
      // Return an arbitrary test ID.
      ->willReturn(__METHOD__);

    $connection = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->setMethods(['insert'])
      ->getMockForAbstractClass();
    $connection->expects($this->once())
      ->method('insert')
      ->willReturn($insert);

    // Mock public stream wrapper enough for simpletest_run_tests().
    $public = $this->getMockBuilder(PublicStream::class)
      ->disableOriginalConstructor()
      // Mock all methods to do nothing and return NULL.
      ->setMethods([])
      ->getMock();

    // Set up the container.
    $this->container->set('database', $connection);
    $this->container->set('stream_wrapper.public', $public);

    // Make sure our mocked database is in use by expecting a test ID that is
    // __METHOD__.
    $this->assertEquals(__METHOD__, simpletest_run_tests([static::class]));
  }

  /**
   * @expectedDeprecation The deprecated hook hook_test_finished() is implemented in these functions: simpletest_deprecation_test_test_finished(). Convert your test to a PHPUnit-based one and implement test listeners. See https://www.drupal.org/node/2934242
   */
  public function testHookTestFinished() {
    // Mock test_discovery.
    $discovery = $this->getMockBuilder(TestDiscovery::class)
      ->disableOriginalConstructor()
      ->setMethods(['registerTestNamespaces'])
      ->getMock();
    $discovery->expects($this->once())
      ->method('registerTestNamespaces')
      ->willReturn([]);

    // Mock renderer.
    $renderer = $this->getMockBuilder(Renderer::class)
      ->disableOriginalConstructor()
      ->setMethods(['render'])
      ->getMock();
    // We don't care what the rendered batch elements look like.
    $renderer->expects($this->any())
      ->method('render')
      ->willReturn('');

    // Set up the container.
    $this->container->set('test_discovery', $discovery);
    $this->container->set('renderer', $renderer);

    // A mock batch.
    $context = [];

    // InnocuousTest is a WebTestBase test class which passes and never touches
    // the database.
    _simpletest_batch_operation([InnocuousTest::class], __METHOD__, $context);
  }

}

/**
 * A very simple WebTestBase test that never touches the database.
 *
 * @group WebTestBase
 * @group legacy
 */
class InnocuousTest extends WebTestBase {

  /**
   * Override to prevent any environmental side-effects.
   */
  protected function prepareEnvironment() {
  }

  /**
   * Override run() since it uses TestBase.
   */
  public function run(array $methods = []) {
  }

  /**
   * Override to prevent any assertions from being stored.
   */
  protected function storeAssertion(array $assertion) {
  }

  /**
   * Override to prevent any assertions from being stored.
   */
  public static function insertAssert($test_id, $test_class, $status, $message = '', $group = 'Other', array $caller = []) {
  }

}
