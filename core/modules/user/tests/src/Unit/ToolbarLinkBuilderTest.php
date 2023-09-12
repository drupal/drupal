<?php

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\ToolbarLinkBuilder;

/**
 * Tests user's ToolbarLinkBuilder.
 *
 * @coversDefaultClass \Drupal\user\ToolbarLinkBuilder
 * @group user
 */
class ToolbarLinkBuilderTest extends UnitTestCase {

  /**
   * Tests structure of display name render array.
   *
   * @covers ::renderDisplayName
   */
  public function testRenderDisplayName() {
    $account = $this->prophesize(AccountProxyInterface::class);
    $display_name = 'Something suspicious that should be #plain_text, not #markup';
    $account->getDisplayName()->willReturn($display_name);
    $toolbar_link_builder = new ToolbarLinkBuilder($account->reveal());
    $expected = ['#plain_text' => $display_name];
    $this->assertSame($expected, $toolbar_link_builder->renderDisplayName());
  }

}
