<?php

namespace Drupal\Tests\simpletest\Unit;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\simpletest\TestDiscovery;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\simpletest\TestDiscovery
 *
 * @group simpletest
 * @group legacy
 */
class TestDiscoveryTest extends UnitTestCase {

  protected function setupVfsWithTestClasses() {
    vfsStream::setup('drupal');

    $test_file = <<<EOF
<?php

/**
 * Test description
 * @group example
 */
class FunctionalExampleTest {}
EOF;

    $test_profile_info = <<<EOF
name: Testing
type: profile
core: 8.x
EOF;

    $test_module_info = <<<EOF
name: Testing
type: module
core: 8.x
EOF;

    vfsStream::create([
      'modules' => [
        'test_module' => [
          'tests' => [
            'src' => [
              'Functional' => [
                'FunctionalExampleTest.php' => $test_file,
                'FunctionalExampleTest2.php' => str_replace(['FunctionalExampleTest', '@group example'], ['FunctionalExampleTest2', '@group example2'], $test_file),
              ],
              'Kernel' => [
                'KernelExampleTest3.php' => str_replace(['FunctionalExampleTest', '@group example'], ['KernelExampleTest3', "@group example2\n * @group kernel\n"], $test_file),
                'KernelExampleTestBase.php' => str_replace(['FunctionalExampleTest', '@group example'], ['KernelExampleTestBase', '@group example2'], $test_file),
                'KernelExampleTrait.php' => str_replace(['FunctionalExampleTest', '@group example'], ['KernelExampleTrait', '@group example2'], $test_file),
                'KernelExampleInterface.php' => str_replace(['FunctionalExampleTest', '@group example'], ['KernelExampleInterface', '@group example2'], $test_file),
              ],
            ],
          ],
        ],
      ],
      'profiles' => [
        'test_profile' => [
          'test_profile.info.yml' => $test_profile_info,
          'modules' => [
            'test_profile_module' => [
              'test_profile_module.info.yml' => $test_module_info,
              'tests' => [
                'src' => [
                  'Kernel' => [
                    'KernelExampleTest4.php' => str_replace(['FunctionalExampleTest', '@group example'], ['KernelExampleTest4', '@group example3'], $test_file),
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
  }

  /**
   * Mock a TestDiscovery object to return specific extension values.
   */
  protected function getTestDiscoveryMock($app_root, $extensions) {
    $class_loader = $this->prophesize(ClassLoader::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $test_discovery = $this->getMockBuilder(TestDiscovery::class)
      ->setConstructorArgs([$app_root, $class_loader->reveal(), $module_handler->reveal()])
      ->setMethods(['getExtensions'])
      ->getMock();

    $test_discovery->expects($this->any())
      ->method('getExtensions')
      ->willReturn($extensions);

    return $test_discovery;
  }

  /**
   * @covers ::getTestClasses
   */
  public function testGetTestClasses() {
    $this->setupVfsWithTestClasses();
    $extensions = [
      'test_module' => new Extension('vfs://drupal', 'module', 'modules/test_module/test_module.info.yml'),
    ];
    $test_discovery = $this->getTestDiscoveryMock('vfs://drupal', $extensions);

    $result = $test_discovery->getTestClasses();
    $this->assertCount(3, $result);
    $this->assertEquals([
      'example' => [
        'Drupal\Tests\test_module\Functional\FunctionalExampleTest' => [
          'name' => 'Drupal\Tests\test_module\Functional\FunctionalExampleTest',
          'description' => 'Test description',
          'group' => 'example',
          'groups' => ['example'],
          'type' => 'PHPUnit-Functional',
        ],
      ],
      'example2' => [
        'Drupal\Tests\test_module\Functional\FunctionalExampleTest2' => [
          'name' => 'Drupal\Tests\test_module\Functional\FunctionalExampleTest2',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2'],
          'type' => 'PHPUnit-Functional',
        ],
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2', 'kernel'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
      'kernel' => [
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2', 'kernel'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
    ], $result);
  }

  /**
   * @covers ::getTestClasses
   */
  public function testGetTestClassesWithSelectedTypes() {
    $this->setupVfsWithTestClasses();
    $extensions = [
      'test_module' => new Extension('vfs://drupal', 'module', 'modules/test_module/test_module.info.yml'),
      'test_profile_module' => new Extension('vfs://drupal', 'profile', 'profiles/test_profile/modules/test_profile_module/test_profile_module.info.yml'),
    ];
    $test_discovery = $this->getTestDiscoveryMock('vfs://drupal', $extensions);

    $result = $test_discovery->getTestClasses(NULL, ['PHPUnit-Kernel']);
    $this->assertCount(4, $result);
    $this->assertEquals([
      'example' => [],
      'example2' => [
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2', 'kernel'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
      'kernel' => [
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2', 'kernel'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
      'example3' => [
        'Drupal\Tests\test_profile_module\Kernel\KernelExampleTest4' => [
          'name' => 'Drupal\Tests\test_profile_module\Kernel\KernelExampleTest4',
          'description' => 'Test description',
          'group' => 'example3',
          'groups' => ['example3'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
    ], $result);
  }

  /**
   * @covers ::getTestClasses
   */
  public function testGetTestsInProfiles() {
    $this->setupVfsWithTestClasses();
    $class_loader = $this->prophesize(ClassLoader::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $container = new Container();
    $container->set('kernel', new DrupalKernel('prod', new ClassLoader()));
    $container->set('site.path', 'sites/default');
    \Drupal::setContainer($container);

    $test_discovery = new TestDiscovery('vfs://drupal', $class_loader->reveal(), $module_handler->reveal());

    $result = $test_discovery->getTestClasses(NULL, ['PHPUnit-Kernel']);
    $expected = [
      'example3' => [
        'Drupal\Tests\test_profile_module\Kernel\KernelExampleTest4' => [
          'name' => 'Drupal\Tests\test_profile_module\Kernel\KernelExampleTest4',
          'description' => 'Test description',
          'group' => 'example3',
          'groups' => ['example3'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
    ];
    $this->assertEquals($expected, $result);
  }

}
