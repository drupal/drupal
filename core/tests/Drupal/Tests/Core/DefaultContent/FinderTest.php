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
    $actual_order = array_keys($finder->data);

    $node_uuid = 'e1714f23-70c0-4493-8e92-af1901771921';
    // The author of the node should come before the node itself. We're using
    // named arguments here purely for clarity.
    $this->assertRelativeOrder(
      $actual_order,
      earlier: '94503467-be7f-406c-9795-fc25baa22203',
      later: $node_uuid,
    );
    // Same with the taxonomy term referenced by the node.
    $this->assertRelativeOrder(
      $actual_order,
      earlier: '550f86ad-aa11-4047-953f-636d42889f85',
      later: $node_uuid,
    );
    // The menu link to the node should come after the node.
    $this->assertRelativeOrder(
      $actual_order,
      earlier: $node_uuid,
      later: '3434bd5a-d2cd-4f26-bf79-a7f6b951a21b',
    );

    // A node that is in a workspace should come after the workspace itself.
    $this->assertRelativeOrder(
      $actual_order,
      earlier: '384c4c10-cc41-4d7e-a1cc-85d1cdc9e87d',
      later: '48475954-e878-439c-9d3d-226724a44269',
    );
  }

  /**
   * Asserts that an item in an array comes before another item in that array.
   *
   * @param array $haystack
   *   The array to examine.
   * @param mixed $earlier
   *   The item which should come first.
   * @param mixed $later
   *   The item which should come after.
   */
  private function assertRelativeOrder(array $haystack, mixed $earlier, mixed $later): void {
    $haystack = array_values($haystack);
    $earlier_index = array_search($earlier, $haystack, TRUE);
    $later_index = array_search($later, $haystack, TRUE);
    $this->assertIsInt($earlier_index);
    $this->assertIsInt($later_index);
    // "Later" should be greater than "earlier".
    $this->assertGreaterThan($earlier_index, $later_index);
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
