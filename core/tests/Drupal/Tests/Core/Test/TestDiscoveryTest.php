<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Test\Exception\MissingGroupException;
use Drupal\Core\Test\TestDiscovery;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\Test\TestDiscovery
 * @group Test
 */
class TestDiscoveryTest extends UnitTestCase {

  /**
   * @covers ::getTestInfo
   * @dataProvider infoParserProvider
   */
  public function testTestInfoParser($expected, $classname, $doc_comment = NULL) {
    $info = TestDiscovery::getTestInfo($classname, $doc_comment);
    $this->assertEquals($expected, $info);
  }

  public function infoParserProvider() {
    // A module provided unit test.
    $tests[] = [
      // Expected result.
      [
        'name' => static::class,
        'group' => 'Test',
        'groups' => ['Test'],
        'description' => 'Tests \Drupal\Core\Test\TestDiscovery.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      static::class,
    ];

    // A core unit test.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\Core\DrupalTest',
        'group' => 'DrupalTest',
        'groups' => ['DrupalTest'],
        'description' => 'Tests \Drupal.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      'Drupal\Tests\Core\DrupalTest',
    ];

    // Functional PHPUnit test.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\FunctionalTests\BrowserTestBaseTest',
        'group' => 'browsertestbase',
        'groups' => ['browsertestbase', '#slow'],
        'description' => 'Tests BrowserTestBase functionality.',
        'type' => 'PHPUnit-Functional',
      ],
      // Classname.
      'Drupal\FunctionalTests\BrowserTestBaseTest',
    ];

    // kernel PHPUnit test.
    $tests['phpunit-kernel'] = [
      // Expected result.
      [
        'name' => '\Drupal\Tests\file\Kernel\FileItemValidationTest',
        'group' => 'file',
        'groups' => ['file'],
        'description' => 'Tests that files referenced in file and image fields are always validated.',
        'type' => 'PHPUnit-Kernel',
      ],
      // Classname.
      '\Drupal\Tests\file\Kernel\FileItemValidationTest',
    ];

    // Test with a different amount of leading spaces.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\ExampleTest',
        'group' => 'test',
        'groups' => ['test'],
        'description' => 'Example test.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      'Drupal\Tests\ExampleTest',
      // Doc block.
      "/**
   * Example test.
   *
   * @group test
   */
 ",
    ];

    // Make sure that a "* @" inside a string does not get parsed as an
    // annotation.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\ExampleTest',
        'group' => 'test',
        'groups' => ['test'],
        'description' => 'Example test. * @',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      'Drupal\Tests\ExampleTest',
      // Doc block.
      "/**
   * Example test. * @
   *
   * @group test
   */
 ",
    ];

    // Multiple @group annotations.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\ExampleTest',
        'group' => 'test1',
        'groups' => ['test1', 'test2'],
        'description' => 'Example test.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      'Drupal\Tests\ExampleTest',
      // Doc block.
      "/**
 * Example test.
 *
 * @group test1
 * @group test2
 */
 ",
    ];

    // A great number of @group annotations.
    $tests['many-group-annotations'] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\ExampleTest',
        'group' => 'test1',
        'groups' => ['test1', 'test2', 'another', 'more', 'many', 'enough', 'whoa'],
        'description' => 'Example test.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      'Drupal\Tests\ExampleTest',
      // Doc block.
      "/**
 * Example test.
 *
 * @group test1
 * @group test2
 * @group another
 * @group more
 * @group many
 * @group enough
 * @group whoa
 */
 ",
    ];

    // Multi-line summary line.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\ExampleTest',
        'description' => 'Example test. And the summary line continues and there is no gap to the annotation.',
        'type' => 'PHPUnit-Unit',
        'group' => 'test',
        'groups' => ['test'],
      ],
      // Classname.
      'Drupal\Tests\ExampleTest',
      // Doc block.
      "/**
 * Example test. And the summary line continues and there is no gap to the
 * annotation.
 *
 * @group test
 */
 ",
    ];
    return $tests;
  }

  /**
   * @covers ::getTestInfo
   */
  public function testTestInfoParserMissingGroup() {
    $classname = 'Drupal\KernelTests\field\BulkDeleteTest';
    $doc_comment = <<<EOT
/**
 * Bulk delete storages and fields, and clean up afterwards.
 */
EOT;
    $this->expectException(MissingGroupException::class);
    $this->expectExceptionMessage('Missing @group annotation in Drupal\KernelTests\field\BulkDeleteTest');
    TestDiscovery::getTestInfo($classname, $doc_comment);
  }

  /**
   * @covers ::getTestInfo
   */
  public function testTestInfoParserMissingSummary() {
    $classname = 'Drupal\KernelTests\field\BulkDeleteTest';
    $doc_comment = <<<EOT
/**
 * @group field
 */
EOT;
    $info = TestDiscovery::getTestInfo($classname, $doc_comment);
    $this->assertEmpty($info['description']);
  }

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
core_version_requirement: '*'
EOF;

    $test_module_info = <<<EOF
name: Testing
type: module
core_version_requirement: '*'
EOF;

    vfsStream::create([
      'modules' => [
        'test_module' => [
          'test_module.info.yml' => $test_module_info,
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
   * Mock a TestDiscovery object to return specific extension values.
   */
  protected function getTestDiscoveryMock($app_root, $extensions) {
    $class_loader = $this->prophesize(ClassLoader::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $test_discovery = $this->getMockBuilder(TestDiscovery::class)
      ->setConstructorArgs([$app_root, $class_loader->reveal(), $module_handler->reveal()])
      ->onlyMethods(['getExtensions'])
      ->getMock();

    $test_discovery->expects($this->any())
      ->method('getExtensions')
      ->willReturn($extensions);

    return $test_discovery;
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

    $container = new Container();
    $container->set('kernel', new DrupalKernel('prod', new ClassLoader()));
    $container->setParameter('site.path', 'sites/default');
    \Drupal::setContainer($container);

    $test_discovery = new TestDiscovery('vfs://drupal', $class_loader->reveal());

    $result = $test_discovery->getTestClasses('test_profile_module', ['PHPUnit-Kernel']);
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

  /**
   * @covers ::getPhpunitTestSuite
   * @dataProvider providerTestGetPhpunitTestSuite
   */
  public function testGetPhpunitTestSuite($classname, $expected) {
    $this->assertEquals($expected, TestDiscovery::getPhpunitTestSuite($classname));
  }

  public function providerTestGetPhpunitTestSuite() {
    $data = [];
    $data['simpletest-web test'] = ['\Drupal\rest\Tests\NodeTest', FALSE];
    $data['module-unittest'] = [static::class, 'Unit'];
    $data['module-kernel test'] = ['\Drupal\KernelTests\Core\Theme\TwigMarkupInterfaceTest', 'Kernel'];
    $data['module-functional test'] = ['\Drupal\FunctionalTests\BrowserTestBaseTest', 'Functional'];
    $data['module-functional javascript test'] = ['\Drupal\Tests\toolbar\FunctionalJavascript\ToolbarIntegrationTest', 'FunctionalJavascript'];
    $data['core-unittest'] = ['\Drupal\Tests\ComposerIntegrationTest', 'Unit'];
    $data['core-unittest2'] = ['Drupal\Tests\Core\DrupalTest', 'Unit'];
    $data['core-unittest3'] = ['Drupal\Tests\Scripts\TestSiteApplicationTest', 'Unit'];
    $data['core-kernel test'] = ['\Drupal\KernelTests\KernelTestBaseTest', 'Kernel'];
    $data['core-functional test'] = ['\Drupal\FunctionalTests\ExampleTest', 'Functional'];
    $data['core-functional javascript test'] = ['\Drupal\FunctionalJavascriptTests\ExampleTest', 'FunctionalJavascript'];
    $data['core-build test'] = ['\Drupal\BuildTests\Framework\Tests\BuildTestTest', 'Build'];

    return $data;
  }

  /**
   * Ensure that classes are not reflected when the docblock is empty.
   *
   * @covers ::getTestInfo
   */
  public function testGetTestInfoEmptyDocblock() {
    // If getTestInfo() performed reflection, it won't be able to find the
    // class we asked it to analyze, so it will throw a ReflectionException.
    // We want to make sure it didn't do that, because we already did some
    // analysis and already have an empty docblock. getTestInfo() will throw
    // MissingGroupException because the annotation is empty.
    $this->expectException(MissingGroupException::class);
    TestDiscovery::getTestInfo('Drupal\Tests\ThisTestDoesNotExistTest', '');
  }

  /**
   * Ensure TestDiscovery::scanDirectory() ignores certain abstract file types.
   *
   * @covers ::scanDirectory
   */
  public function testScanDirectoryNoAbstract() {
    $this->setupVfsWithTestClasses();
    $files = TestDiscovery::scanDirectory('Drupal\\Tests\\test_module\\Kernel\\', vfsStream::url('drupal/modules/test_module/tests/src/Kernel'));
    $this->assertNotEmpty($files);
    $this->assertArrayNotHasKey('Drupal\Tests\test_module\Kernel\KernelExampleTestBase', $files);
    $this->assertArrayNotHasKey('Drupal\Tests\test_module\Kernel\KernelExampleTrait', $files);
    $this->assertArrayNotHasKey('Drupal\Tests\test_module\Kernel\KernelExampleInterface', $files);
    $this->assertArrayHasKey('Drupal\Tests\test_module\Kernel\KernelExampleTest3', $files);
  }

}
