<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the admin listing fallback when views is not enabled.
 *
 * @group node
 */
class NodeListBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
  }

  /**
   * Tests that the correct cache contexts are set.
   */
  public function testCacheContexts(): void {
    /** @var \Drupal\Core\Entity\EntityListBuilderInterface $list_builder */
    $list_builder = $this->container->get('entity_type.manager')->getListBuilder('node');

    $build = $list_builder->render();
    $this->container->get('renderer')->renderRoot($build);

    $this->assertEqualsCanonicalizing([
      'languages:' . LanguageInterface::TYPE_INTERFACE,
      'theme',
      'url.query_args.pagers:0',
      'user.node_grants:view',
      'user.permissions',
    ],
    $build['#cache']['contexts']);
  }

}
