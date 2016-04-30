<?php

/**
 * @file
 * Contains \Drupal\Tests\simpletest\Unit\TestInfoParsingTest.
 */

namespace Drupal\Tests\simpletest\Unit {

use Composer\Autoload\ClassLoader;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\simpletest\TestDiscovery;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\simpletest\TestDiscovery
 * @group simpletest
 */
class TestInfoParsingTest extends UnitTestCase {

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
        'name' => 'Drupal\Tests\simpletest\Unit\TestInfoParsingTest',
        'group' => 'simpletest',
        'description' => 'Tests \Drupal\simpletest\TestDiscovery.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      'Drupal\Tests\simpletest\Unit\TestInfoParsingTest',
    ];

    // A core unit test.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\Tests\Core\DrupalTest',
        'group' => 'DrupalTest',
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
        'name' => 'Drupal\Tests\simpletest\Functional\BrowserTestBaseTest',
        'group' => 'simpletest',
        'description' => 'Tests BrowserTestBase functionality.',
        'type' => 'PHPUnit-Functional',
      ],
      // Classname.
      'Drupal\Tests\simpletest\Functional\BrowserTestBaseTest',
    ];

    // kernel PHPUnit test.
    $tests['phpunit-kernel'] = [
      // Expected result.
      [
        'name' => '\Drupal\Tests\file\Kernel\FileItemValidationTest',
        'group' => 'file',
        'description' => 'Tests that files referenced in file and image fields are always validated.',
        'type' => 'PHPUnit-Kernel',
      ],
      // Classname.
      '\Drupal\Tests\file\Kernel\FileItemValidationTest',
    ];

    // Simpletest classes can not be autoloaded in a PHPUnit test, therefore
    // provide a docblock.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\simpletest\Tests\ExampleSimpleTest',
        'group' => 'simpletest',
        'description' => 'Tests the Simpletest UI internal browser.',
        'type' => 'Simpletest',
      ],
      // Classname.
      'Drupal\simpletest\Tests\ExampleSimpleTest',
      // Doc block.
      "/**
 * Tests the Simpletest UI internal browser.
 *
 * @group simpletest
 */
 ",
    ];

    // Test with a different amount of leading spaces.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\simpletest\Tests\ExampleSimpleTest',
        'group' => 'simpletest',
        'description' => 'Tests the Simpletest UI internal browser.',
        'type' => 'Simpletest',
      ],
      // Classname.
      'Drupal\simpletest\Tests\ExampleSimpleTest',
      // Doc block.
      "/**
   * Tests the Simpletest UI internal browser.
   *
   * @group simpletest
   */
   */
 ",
    ];

    // Make sure that a "* @" inside a string does not get parsed as an
    // annotation.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\simpletest\Tests\ExampleSimpleTest',
        'group' => 'simpletest',
        'description' => 'Tests the Simpletest UI internal browser. * @',
        'type' => 'Simpletest',
      ],
      // Classname.
      'Drupal\simpletest\Tests\ExampleSimpleTest',
      // Doc block.
      "/**
   * Tests the Simpletest UI internal browser. * @
   *
   * @group simpletest
   */
 ",
    ];

    // Multiple @group annotations.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\simpletest\Tests\ExampleSimpleTest',
        'group' => 'Test',
        'description' => 'Tests the Simpletest UI internal browser.',
        'type' => 'Simpletest',
      ],
      // Classname.
      'Drupal\simpletest\Tests\ExampleSimpleTest',
      // Doc block.
      "/**
 * Tests the Simpletest UI internal browser.
 *
 * @group Test
 * @group simpletest
 */
 ",
    ];

    // @dependencies annotation.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\simpletest\Tests\ExampleSimpleTest',
        'description' => 'Tests the Simpletest UI internal browser.',
        'type' => 'Simpletest',
        'requires' => ['module' => ['test']],
        'group' => 'simpletest',
      ],
      // Classname.
      'Drupal\simpletest\Tests\ExampleSimpleTest',
      // Doc block.
      "/**
 * Tests the Simpletest UI internal browser.
 *
 * @dependencies test
 * @group simpletest
 */
 ",
    ];

    // Multiple @dependencies annotation.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\simpletest\Tests\ExampleSimpleTest',
        'description' => 'Tests the Simpletest UI internal browser.',
        'type' => 'Simpletest',
        'requires' => ['module' => ['test', 'test1', 'test2']],
        'group' => 'simpletest',
      ],
      // Classname.
      'Drupal\simpletest\Tests\ExampleSimpleTest',
      // Doc block.
      "/**
 * Tests the Simpletest UI internal browser.
 *
 * @dependencies test, test1, test2
 * @group simpletest
 */
 ",
    ];

    // Multi-line summary line.
    $tests[] = [
      // Expected result.
      [
        'name' => 'Drupal\simpletest\Tests\ExampleSimpleTest',
        'description' => 'Tests the Simpletest UI internal browser. And the summary line continues an there is no gap to the annotation.',
        'type' => 'Simpletest',
        'group' => 'simpletest',
      ],
      // Classname.
      'Drupal\simpletest\Tests\ExampleSimpleTest',
      // Doc block.
      "/**
 * Tests the Simpletest UI internal browser. And the summary line continues an
 * there is no gap to the annotation.
 *
 * @group simpletest
 */
 ",
    ];
    return $tests;
  }

  /**
   * @covers ::getTestInfo
   * @expectedException \Drupal\simpletest\Exception\MissingGroupException
   * @expectedExceptionMessage Missing @group annotation in Drupal\KernelTests\field\BulkDeleteTest
   */
  public function testTestInfoParserMissingGroup() {
    $classname = 'Drupal\KernelTests\field\BulkDeleteTest';
    $doc_comment = <<<EOT
/**
 * Bulk delete storages and fields, and clean up afterwards.
 */
EOT;
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
                'KernelExampleTest3.php' => str_replace(['FunctionalExampleTest', '@group example'], ['KernelExampleTest3', '@group example2'], $test_file),
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
    $class_loader = $this->prophesize(ClassLoader::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $test_discovery = new TestTestDiscovery('vfs://drupal', $class_loader->reveal(), $module_handler->reveal());

    $extensions = [
      'test_module' => new Extension('vfs://drupal', 'module', 'modules/test_module/test_module.info.yml'),
    ];
    $test_discovery->setExtensions($extensions);
    $result = $test_discovery->getTestClasses();
    $this->assertCount(2, $result);
    $this->assertEquals([
      'example' => [
        'Drupal\Tests\test_module\Functional\FunctionalExampleTest' => [
          'name' => 'Drupal\Tests\test_module\Functional\FunctionalExampleTest',
          'description' => 'Test description',
          'group' => 'example',
          'type' => 'PHPUnit-Functional',
        ],
      ],
      'example2' => [
        'Drupal\Tests\test_module\Functional\FunctionalExampleTest2' => [
          'name' => 'Drupal\Tests\test_module\Functional\FunctionalExampleTest2',
          'description' => 'Test description',
          'group' => 'example2',
          'type' => 'PHPUnit-Functional',
        ],
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
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
    $class_loader = $this->prophesize(ClassLoader::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $test_discovery = new TestTestDiscovery('vfs://drupal', $class_loader->reveal(), $module_handler->reveal());

    $extensions = [
      'test_module' => new Extension('vfs://drupal', 'module', 'modules/test_module/test_module.info.yml'),
    ];
    $test_discovery->setExtensions($extensions);
    $result = $test_discovery->getTestClasses(NULL, ['PHPUnit-Kernel']);
    $this->assertCount(2, $result);
    $this->assertEquals([
      'example' => [
      ],
      'example2' => [
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'type' => 'PHPUnit-Kernel',
        ],
      ],
    ], $result);
  }

}

class TestTestDiscovery extends TestDiscovery {

  /**
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $extensions = [];

  public function setExtensions(array $extensions) {
    $this->extensions = $extensions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensions() {
    return $this->extensions;
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
    $data['simpletest-webtest'] = ['\Drupal\rest\Tests\NodeTest', FALSE];
    $data['simpletest-kerneltest'] = ['\Drupal\hal\Tests\FileNormalizeTest', FALSE];
    $data['module-unittest'] = [static::class, 'Unit'];
    $data['module-kerneltest'] = ['\Drupal\KernelTests\Core\Theme\TwigMarkupInterfaceTest', 'Kernel'];
    $data['module-functionaltest'] = ['\Drupal\Tests\simpletest\Functional\BrowserTestBaseTest', 'Functional'];
    $data['module-functionaljavascripttest'] = ['\Drupal\Tests\toolbar\FunctionalJavascript\ToolbarIntegrationTest', 'FunctionalJavascript'];
    $data['core-unittest'] = ['\Drupal\Tests\ComposerIntegrationTest', 'Unit'];
    $data['core-unittest2'] = ['Drupal\Tests\Core\DrupalTest', 'Unit'];
    $data['core-kerneltest'] = ['\Drupal\KernelTests\KernelTestBaseTest', 'Kernel'];
    $data['core-functionaltest'] = ['\Drupal\FunctionalTests\ExampleTest', 'Functional'];
    $data['core-functionaljavascripttest'] = ['\Drupal\FunctionalJavascriptTests\ExampleTest', 'FunctionalJavascript'];

    return $data;
  }

}

}

namespace Drupal\simpletest\Tests {

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Simpletest UI internal browser.
 *
 * @group simpletest
 */
class ExampleSimpleTest extends WebTestBase {
}

}
