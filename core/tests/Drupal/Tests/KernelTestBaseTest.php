<?php

/**
 * @file
 * Contains \Drupal\Tests\KernelTestBaseTest.
 */

namespace Drupal\Tests;

/**
 * @coversDefaultClass \Drupal\Tests\KernelTestBase
 * @group PHPUnit
 */
class KernelTestBaseTest extends KernelTestBase {

//  protected function setUp() {
//    parent::setUp();
//  }

  /**
   * @covers ::setUpBeforeClass
   */
  public function testSetUpBeforeClass() {
    // Note: PHPUnit automatically restores the original working directory.
    $this->assertSame(realpath(__DIR__ . '/../../../../'), getcwd());
  }

  /**
   * @covers ::prepareEnvironment
   */
  public function testPrepareEnvironment() {
    $this->assertStringStartsWith('sites/simpletest/', $this->siteDirectory);
    $this->assertEquals('', $this->databasePrefix);
  }

  /**
   * @covers ::__get
   * @expectedException \RuntimeException
   * @dataProvider providerTestGet
   */
  public function testGet($property) {
    $this->$property;
  }

  public function providerTestGet() {
    return [
      ['originalWhatever'],
      ['public_files_directory'],
      ['private_files_directory'],
      ['temp_files_directory'],
      ['translation_files_directory'],
      ['generatedTestFiles'],
    ];
  }

  /**
   * @covers ::setUp
   */
  public function testSetUp() {
    $this->assertTrue($this->container->has('request_stack'));
    $this->assertTrue($this->container->initialized('request_stack'));
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $this->assertNotEmpty($request);
    $this->assertEquals('/', $request->getPathInfo());

    $this->assertSame($request, \Drupal::request());

    $this->assertEquals($this, $GLOBALS['conf']['container_service_providers']['test']);

    $GLOBALS['destroy-me'] = TRUE;
    $this->assertArrayHasKey('destroy-me', $GLOBALS);

    $schema = $this->container->get('database')->schema();
    $schema->createTable('foo', array(
      'fields' => array(
        'name' => array(
          'type' => 'varchar',
        ),
      ),
    ));
    $this->assertTrue($schema->tableExists('foo'));
  }

  /**
   * @covers ::setUp
   * @depends testSetUp
   */
  public function testSetUpDoesNotLeak() {
    $this->assertArrayNotHasKey('destroy-me', $GLOBALS);

    $expected = array(
      'config' => 'config',
    );
    $schema = $this->container->get('database')->schema();
    $this->assertEquals($expected, $schema->findTables('%'));
  }

  /**
   * @covers ::register
   */
  public function testRegister() {
    // Verify that our container is identical to the actual container.
    $this->assertInstanceOf('Symfony\Component\DependencyInjection\ContainerInterface', $this->container);
    $this->assertSame($this->container, \Drupal::getContainer());

    // Request should not exist anymore.
    $this->assertFalse($this->container->has('request'));

    // Verify that there is a request stack.
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
    $this->assertSame($request, \Drupal::request());

    // Trigger a container rebuild.
    $this->enableModules(array('system'));

    // Verify that our container is identical to the actual container.
    $this->assertInstanceOf('Symfony\Component\DependencyInjection\ContainerInterface', $this->container);
    $this->assertSame($this->container, \Drupal::getContainer());

    // Request should not exist anymore.
    $this->assertFalse($this->container->has('request'));

    // Verify that there is a request stack (and that it persisted).
    $new_request = $this->container->get('request_stack')->getCurrentRequest();
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $new_request);
    $this->assertSame($new_request, \Drupal::request());
    $this->assertSame($request, $new_request);
  }

  /**
   * @covers ::log
   * @expectedException \ErrorException
   * @expectedExceptionCode WATCHDOG_WARNING
   * @expectedExceptionMessage Some problem.
   */
  public function testLog() {
    watchdog('system', 'Not a problem.', array(), WATCHDOG_NOTICE);
    watchdog('system', 'Some problem.', array(), WATCHDOG_WARNING);
  }

}
