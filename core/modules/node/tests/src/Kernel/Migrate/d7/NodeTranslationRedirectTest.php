<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests node translation redirects.
 *
 * @group migrate_drupal
 * @group node
 */
class NodeTranslationRedirectTest extends MigrateDrupal7TestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'menu_ui',
    'node',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser();

    $this->installSchema('node', ['node_access']);

    $this->migrateUsers(FALSE);
    $this->migrateContentTypes();
    $this->executeMigrations([
      'language',
      'd7_language_types',
      'd7_language_negotiation_settings',
      'd7_node',
      'd7_node_translation',
    ]);
  }

  /**
   * Tests that not found node translations are redirected.
   */
  public function testNodeTranslationRedirect(): void {
    $kernel = $this->container->get('http_kernel');
    $request = Request::create('/node/3');
    $response = $kernel->handle($request);
    $this->assertSame(301, $response->getStatusCode());
    $this->assertSame('/node/2', $response->getTargetUrl());
  }

}
