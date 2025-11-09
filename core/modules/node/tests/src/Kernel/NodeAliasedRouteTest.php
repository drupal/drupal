<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests aliased Node routes.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class NodeAliasedRouteTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * Test URL generation using node.add_page matches entity.node.add_page.
   */
  public function testNodeAddPageRouteAlias(): void {
    $url_for_current_route = Url::fromRoute('entity.node.add_page')->toString();
    $url_for_bc_route = Url::fromRoute('node.add_page')->toString();
    $this->assertSame($url_for_current_route, $url_for_bc_route);
  }

  /**
   * Test URL generation using node.add matches entity.node.add_form.
   */
  public function testNodeAddRouteAlias(): void {
    $url_for_current_route = Url::fromRoute('entity.node.add_form', ['node_type' => 'page'])->toString();
    $url_for_bc_route = Url::fromRoute('node.add', ['node_type' => 'page'])->toString();
    $this->assertSame($url_for_current_route, $url_for_bc_route);
  }

}
