<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\ClassLoader;

use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\KernelTests\KernelTestBase;
use Drupal\module_autoload_test\Foo;

/**
 * @coversDefaultClass Drupal\Core\ClassLoader\BackwardsCompatibilityClassLoader
 * @group ClassLoader
 */
class BackwardsCompatibilityClassLoaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['module_autoload_test'];

  /**
   * Tests that the bc layer for TranslationWrapper works.
   */
  public function testTranslationWrapper(): void {
    // @phpstan-ignore class.notFound
    $object = new TranslationWrapper('Backward compatibility');
    $this->assertInstanceOf(TranslatableMarkup::class, $object);
  }

  /**
   * Tests that a moved class from a module works.
   *
   * @group legacy
   */
  public function testModuleMovedClass():  void {
    // @phpstan-ignore class.notFound
    $this->expectDeprecation('Class ' . Foo::class . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0, use Drupal\Component\Utility\Random instead. See https://www.drupal.org/project/drupal/issues/3502882');
    // @phpstan-ignore class.notFound
    $object = new Foo();
    $this->assertInstanceOf(Random::class, $object);
  }

}
