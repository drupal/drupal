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
   * @covers ::__set
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

}
