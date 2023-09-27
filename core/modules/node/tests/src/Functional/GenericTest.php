<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;

/**
 * Generic module test for node.
 *
 * @group node
 */
class GenericTest extends GenericModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure the generic test base is working as expected.
    $this->assertSame('node', $this->getModule());
    parent::setUp();
  }

}
