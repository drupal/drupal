<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test for installing Layout Builder before Block Content in the same request.
 *
 * @group layout_builder
 */
class LayoutBuilderBlockContentDependencyTest extends KernelTestBase {

  /**
   * Test that block_content can be successfully installed after layout_builder.
   *
   * The InlineBlock plugin class in layout_builder uses
   * RefinableDependentAccessTrait, which used to live in block_content, though
   * block_content is not a layout_builder dependency. Since the BlockContent
   * entity type class also uses the same trait, if, in order and in the same
   * request:
   * 1. layout_builder is installed first without block_content
   * 2. block plugins are discovered
   * 3. block_content is installed,
   * a fatal error can occur, because the trait was missing before block_content
   * is installed and gets aliased to an empty trait. When the installation of
   * the block_content module installs the BlockContent entity type, the empty
   * trait is missing the methods that need to be implemented from the
   * interface.
   *
   * @see \Drupal\Component\Plugin\Discovery\AttributeClassDiscovery
   * @see \Drupal\Component\Discovery\MissingClassDetectionClassLoader
   */
  public function testInstallLayoutBuilderAndBlockContent(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('block_content'));
    // Prevent classes in the block_content modules from being loaded before the
    // module is installed.
    $this->classLoader->setPsr4("Drupal\\block_content\\", '');

    // Install test module that will act on layout_builder being installed and
    // at that time does block plugin discovery first, then installs
    // block_content.
    \Drupal::service('module_installer')->install(['layout_builder_block_content_dependency_test']);

    \Drupal::service('module_installer')->install(['layout_builder']);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('block_content'));
  }

}
