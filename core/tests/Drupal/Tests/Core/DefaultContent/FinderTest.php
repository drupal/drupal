<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DefaultContent;

use Drupal\Core\DefaultContent\Finder;
use Drupal\Core\DefaultContent\ImportException;
use Drupal\Tests\UnitTestCase;

/**
 * @covers \Drupal\Core\DefaultContent\Finder
 * @group DefaultContent
 */
class FinderTest extends UnitTestCase {

  /**
   * Tests that any discovered entity data is sorted into dependency order.
   */
  public function testFoundDataIsInDependencyOrder(): void {
    $finder = new Finder(__DIR__ . '/../../../../fixtures/default_content');

    $expected_order = [
      // First is the author of the node.
      '94503467-be7f-406c-9795-fc25baa22203',
      // Next, the taxonomy term referenced by the node.
      '550f86ad-aa11-4047-953f-636d42889f85',
      // Then we have the node itself, since it has no other dependencies.
      'e1714f23-70c0-4493-8e92-af1901771921',
      // Finally, the menu link to the node.
      '3434bd5a-d2cd-4f26-bf79-a7f6b951a21b',
    ];
    $this->assertSame($expected_order, array_slice(array_keys($finder->data), 0, 4));
  }

  /**
   * Tests that files without UUIDs will raise an exception.
   */
  public function testExceptionIfNoUuid(): void {
    $this->expectException(ImportException::class);
    $this->expectExceptionMessageMatches("#/no-uuid\.yml does not have a UUID\.$#");
    new Finder(__DIR__ . '/../../../../fixtures/default_content_broken');
  }

}
