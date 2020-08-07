<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests node translation redirections.
 *
 * @group migrate_drupal
 * @group node
 */
class NodeTranslationRedirectTest extends MigrateDrupal6TestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser();

    $this->installEntitySchema('node');
    $this->installConfig(['node']);
    $this->installSchema('node', ['node_access']);
    $this->migrateUsers(FALSE);
    $this->migrateFields();

    $this->executeMigrations([
      'language',
      'd6_language_types',
      'd6_language_negotiation_settings',
      'd6_node_settings',
      'd6_node',
      'd6_node_translation',
    ]);
  }

  /**
   * Tests that not found node translations are redirected.
   */
  public function testNodeTranslationRedirect() {
    $kernel = $this->container->get('http_kernel');
    $request = Request::create('/node/11');
    $response = $kernel->handle($request);
    $this->assertSame(301, $response->getStatusCode());
    $this->assertSame('/node/10', $response->getTargetUrl());
  }

}
