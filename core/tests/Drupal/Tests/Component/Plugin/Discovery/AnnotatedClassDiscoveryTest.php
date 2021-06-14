<?php

namespace Drupal\Tests\Component\Plugin\Discovery;

use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\FileCache\FileCacheFactory;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery
 *
 * @group Annotation
 * @group Plugin
 */
class AnnotatedClassDiscoveryTest extends TestCase {

  /**
   * All the Drupal documentation standards tags.
   *
   * @var string[]
   */
  public function provideBadAnnotations() {
    return [
      ['addtogroup'],
      ['code'],
      ['defgroup'],
      ['deprecated'],
      ['endcode'],
      ['endlink'],
      ['file'],
      ['ingroup'],
      ['group'],
      ['link'],
      ['mainpage'],
      ['param'],
      ['ref'],
      ['return'],
      ['section'],
      ['see'],
      ['subsection'],
      ['throws'],
      ['todo'],
      ['var'],
      ['{'],
      ['}'],
    ];
  }

  /**
   * Make sure AnnotatedClassDiscovery never tries to autoload bad annotations.
   *
   * @dataProvider provideBadAnnotations
   *
   * @coversNothing
   */
  public function testAutoloadBadAnnotations($annotation) {
    // Set up a class file in vfsStream.
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('root');
    vfsStreamWrapper::setRoot($root);

    FileCacheFactory::setPrefix(__CLASS__);

    // Make a directory for discovery.
    $url = vfsStream::url('root');
    mkdir($url . '/DrupalTest');

    // Create a class docblock with our annotation.
    $php_file = "<?php\nnamespace DrupalTest;\n/**\n";
    $php_file .= " * @$annotation\n";
    $php_file .= " */\nclass TestClass {}";
    file_put_contents($url . '/DrupalTest/TestClass.php', $php_file);

    // Create an AnnotatedClassDiscovery object referencing the virtual file.
    $discovery = new AnnotatedClassDiscovery(
      ['\\DrupalTest\\TestClass' => [vfsStream::url('root/DrupalTest')]], '\\DrupalTest\\Component\\Annotation\\'
    );

    // Register our class loader which will fail if the annotation reader tries
    // to autoload disallowed annotations.
    $class_loader = function ($class_name) use ($annotation) {
      $name_array = explode('\\', $class_name);
      $name = array_pop($name_array);
      if ($name == $annotation) {
        $this->fail('Attempted to autoload a non-plugin annotation: ' . $name);
      }
    };
    spl_autoload_register($class_loader, TRUE, TRUE);
    // Now try to get plugin definitions.
    $definitions = $discovery->getDefinitions();
    // Unregister to clean up.
    spl_autoload_unregister($class_loader);
    // Assert that no annotations were loaded.
    $this->assertEmpty($definitions);
  }

}
