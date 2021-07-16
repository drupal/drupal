<?php

namespace Drupal\Tests\Core\Test;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Test\TestRunnerKernel;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Core\Test\TestRunnerKernel
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @group Test
 * @group simpletest
 */
class TestRunnerKernelTest extends TestCase {

  /**
   * Data provider for self::testConstructor().
   */
  public function providerTestConstructor() {
    $core = [
      'core' => [
        'modules' => [
          'system' => [
            'system.info.yml' => 'type: module',
          ],
        ],
      ],
    ];
    $tests = [];
    $tests['simpletest-in-contrib'] = [
      $core + [
        'modules' => [
          'contrib' => [
            'simpletest' => [
              'simpletest.info.yml' => 'type: module',
            ],
          ],
        ],
      ],
      'modules/contrib/simpletest',
    ];

    $tests['simpletest-simpletest-in-contrib'] = [
      $core + [
        'modules' => [
          'contrib' => [
            'simpletest-simpletest' => [
              'simpletest.info.yml' => 'type: module',
            ],
          ],
        ],
      ],
      'modules/contrib/simpletest-simpletest',
    ];

    $tests['simpletest-no-contrib'] = [
      $core + [
        'modules' => [
          'simpletest' => [
            'simpletest.info.yml' => 'type: module',
          ],
        ],
      ],
      'modules/simpletest',
    ];

    $tests['no-simpletest'] = [
      $core,
      FALSE,
    ];

    return $tests;
  }

  /**
   * @covers ::__construct
   * @dataProvider providerTestConstructor
   */
  public function testConstructor($file_system, $expected) {
    // Set up the file system.
    $vfs = vfsStream::setup('root');
    vfsStream::create($file_system, $vfs);

    $kernel = new TestRunnerKernel('prod', new ClassLoader(), FALSE, vfsStream::url('root'));
    $class = new \ReflectionClass(TestRunnerKernel::class);
    $instance_method = $class->getMethod('moduleData');
    $instance_method->setAccessible(TRUE);
    /** @var \Drupal\Core\Extension\Extension $extension */
    $extension = $instance_method->invoke($kernel, 'simpletest');

    if ($expected === FALSE) {
      $this->assertFalse($extension);
    }
    else {
      $this->assertInstanceOf(Extension::class, $extension);
      $this->assertSame($expected, $extension->getPath());
    }
  }

}
