<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Component\Utility\Crypt;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests the query parameter workspace negotiator.
 *
 * @coversDefaultClass \Drupal\workspaces\Negotiator\QueryParameterWorkspaceNegotiator
 * @group workspaces
 */
class WorkspaceQueryParameterNegotiatorTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'workspaces',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('workspace');
    $this->installSchema('workspaces', ['workspace_association']);

    // Create a new workspace for testing.
    Workspace::create(['id' => 'stage', 'label' => 'Stage'])->save();

    $this->setCurrentUser($this->createUser(['administer workspaces']));

    // Reset the internal state of the workspace manager so that checking for an
    // active workspace in the test is not influenced by previous actions.
    \Drupal::getContainer()->set('workspaces.manager', NULL);
  }

  /**
   * @covers ::getActiveWorkspaceId
   * @dataProvider providerTestWorkspaceQueryParameter
   */
  public function testWorkspaceQueryParameter(?string $workspace, ?string $token, ?string $negotiated_workspace, bool $has_active_workspace): void {
    // We can't access the settings service in the data provider method, so we
    // generate a good token here.
    if ($token === 'good_token') {
      $hash_salt = $this->container->get('settings')->get('hash_salt');
      $token = substr(Crypt::hmacBase64($workspace, $hash_salt), 0, 8);
    }

    $request = \Drupal::request();
    $request->query->set('workspace', $workspace);
    $request->query->set('token', $token);

    /** @var \Drupal\workspaces\Negotiator\QueryParameterWorkspaceNegotiator $negotiator */
    $negotiator = $this->container->get('workspaces.negotiator.query_parameter');

    $this->assertSame($negotiated_workspace, $negotiator->getActiveWorkspaceId($request));
    $this->assertSame($has_active_workspace, \Drupal::service('workspaces.manager')->hasActiveWorkspace());
  }

  /**
   * Data provider for testWorkspaceQueryParameter.
   */
  public static function providerTestWorkspaceQueryParameter(): array {
    return [
      'no workspace, no token' => [
        'workspace' => NULL,
        'token' => NULL,
        'negotiated_workspace' => NULL,
        'has_active_workspace' => FALSE,
      ],
      'fake workspace, no token' => [
        'workspace' => 'fake_id',
        'token' => NULL,
        'negotiated_workspace' => NULL,
        'has_active_workspace' => FALSE,
      ],
      'fake workspace, fake token' => [
        'workspace' => 'fake_id',
        'token' => 'fake_token',
        'negotiated_workspace' => NULL,
        'has_active_workspace' => FALSE,
      ],
      'good workspace, fake token' => [
        'workspace' => 'stage',
        'token' => 'fake_token',
        'negotiated_workspace' => NULL,
        'has_active_workspace' => FALSE,
      ],
      // The fake workspace will be accepted by the negotiator in this case, but
      // the workspace manager will try to load and check access for it, and
      // won't set it as the active workspace. Note that "fake" can also mean a
      // workspace that existed at some point, then it was deleted and the user
      // is just accessing a stale link.
      'fake workspace, good token' => [
        'workspace' => 'fake_id',
        'token' => 'good_token',
        'negotiated_workspace' => 'fake_id',
        'has_active_workspace' => FALSE,
      ],
      'good workspace, good token' => [
        'workspace' => 'stage',
        'token' => 'good_token',
        'negotiated_workspace' => 'stage',
        'has_active_workspace' => TRUE,
      ],
    ];
  }

}
