<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render;

use Drupal\Core\Render\Element\Table;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Deprecation tests cases for the render layer.
 */
#[Group('legacy')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class RendererLegacyTest extends KernelTestBase {

  /**
   * Tests deprecation of drupal_attach_tabledrag().
   *
   * @see drupal_attach_tabledrag()
   */
  public function testTableDrag(): void {
    $this->expectUserDeprecationMessage('drupal_attach_tabledrag() is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use \Drupal\Core\Render\Element\Table::attachTabledrag() instead. See https://www.drupal.org/node/3035565');
    $elements = [];
    $options = [
      'table_id' => 'test-table',
      'action' => 'match',
      'relationship' => 'sibling',
      'group' => 'test',
    ];
    drupal_attach_tabledrag($elements, $options);
    $expected = [];
    Table::attachTabledrag($expected, $options);
    $this->assertSame($expected['#attached']['drupalSettings']['tableDrag']['test-table']['test'][1], $elements['#attached']['drupalSettings']['tableDrag']['test-table']['test'][0]);
  }

}
