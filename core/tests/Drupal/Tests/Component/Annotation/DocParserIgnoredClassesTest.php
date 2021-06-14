<?php

namespace Drupal\Tests\Component\Annotation;

use Drupal\Component\Annotation\Doctrine\DocParser;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\Doctrine\DocParser
 *
 * @group Annotation
 */
class DocParserIgnoredClassesTest extends TestCase {

  /**
   * Ensure annotations can be ignored when namespaces are present.
   *
   * Drupal's DocParser should never use class_exists() on an ignored
   * annotation, including cases where namespaces are set.
   */
  public function testIgnoredAnnotationSkippedBeforeReflection() {
    $annotation = 'neverReflectThis';
    $parser = new DocParser();
    $parser->setIgnoredAnnotationNames([$annotation => TRUE]);
    $parser->addNamespace('\\Arbitrary\\Namespace');

    // Register our class loader which will fail if the parser tries to
    // autoload disallowed annotations.
    $autoloader = function ($class_name) use ($annotation) {
      $name_array = explode('\\', $class_name);
      $name = array_pop($name_array);
      if ($name == $annotation) {
        $this->fail('Attempted to autoload an ignored annotation: ' . $name);
      }
    };
    spl_autoload_register($autoloader, TRUE, TRUE);
    // Perform the parse.
    $this->assertEmpty($parser->parse('@neverReflectThis'));
    // Clean up after ourselves.
    spl_autoload_unregister($autoloader);
  }

}
