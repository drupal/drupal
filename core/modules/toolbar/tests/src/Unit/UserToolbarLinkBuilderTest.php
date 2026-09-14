<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\Unit;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\toolbar\UserToolbarLinkBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests toolbar's UserToolbarLinkBuilder.
 */
#[CoversClass(UserToolbarLinkBuilder::class)]
#[Group('toolbar')]
#[IgnoreDeprecations]
class UserToolbarLinkBuilderTest extends UnitTestCase {

  /**
   * Tests structure of display name render array.
   */
  public function testRenderDisplayName(): void {
    $account = $this->prophesize(AccountProxyInterface::class);
    $display_name = 'Something suspicious that should be #plain_text, not #markup';
    $account->getDisplayName()->willReturn($display_name);
    $toolbar_link_builder = new UserToolbarLinkBuilder($account->reveal());
    $expected = ['#plain_text' => $display_name];
    $this->assertSame($expected, $toolbar_link_builder->renderDisplayName());
  }

}
