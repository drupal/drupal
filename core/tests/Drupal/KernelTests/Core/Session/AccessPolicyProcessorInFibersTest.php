<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Session;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Session\AccessPolicyProcessor;
use Drupal\Core\Session\UserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the behavior of the access policy processor running inside fibers.
 */
#[CoversClass(AccessPolicyProcessor::class)]
#[Group('Session')]
#[RunTestsInSeparateProcesses]
class AccessPolicyProcessorInFibersTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * Tests the behavior of the access policy processor running inside fibers.
   */
  public function testAccessPolicyProcessorInFibers(): void {
    // Create a role and then empty the static cache, so that it will need to be
    // loaded from storage.
    $this->createRole(['administer modules'], 'test_role');
    \Drupal::entityTypeManager()->getStorage('user_role')->resetCache();

    // Create a render array with two elements that have lazy builders. The
    // first lazy builder prints the ID of the current user. The second lazy
    // builder checks the permissions of a different user, which results in a
    // call to AccountPolicyProcessor::processAccessPolicies(). In that method,
    // if the current user ID is different from the ID of the account being
    // processed, the current user is temporarily switched to that account.
    // This is done in order to make sure the correct user's data is used when
    // saving to the variation cache.
    //
    // Note that for the purposes of this test, the lazy builder that accesses
    // the current user ID has to come before the other lazy builder in the
    // render array. Ordering the array this way results in the second lazy
    // builder starting to run before the first. This happens because as render
    // contexts are updated as they are bubbled, the BubbleableMetadata object
    // associated with the render element merges its attached placeholder to the
    // front of the list to be processed.
    $build = [
      [
        '#lazy_builder' => [self::class . '::lazyBuilderCheckCurrentUserCallback', []],
        '#create_placeholder' => TRUE,
      ],
      [
        // Add a space between placeholders.
        '#markup' => ' ',
      ],
      [
        '#lazy_builder' => [self::class . '::lazyBuilderCheckAccessCallback', []],
        '#create_placeholder' => TRUE,
      ],
    ];

    $user2 = new UserSession(['uid' => 2]);
    $this->setCurrentUser($user2);

    $expected = 'The current user id is 2. User 3 can administer modules.';
    $output = (string) \Drupal::service(RendererInterface::class)->renderRoot($build);
    $this->assertSame($expected, $output);
  }

  /**
   * Lazy builder that displays the current user ID.
   */
  #[TrustedCallback]
  public static function lazyBuilderCheckCurrentUserCallback(): array {
    return [
      '#markup' => new FormattableMarkup('The current user id is @id.', ['@id' => \Drupal::currentUser()->id()]),
    ];
  }

  /**
   * Lazy builder that checks permissions on a different user.
   */
  #[TrustedCallback]
  public static function lazyBuilderCheckAccessCallback(): array {
    $user3 = new UserSession([
      'uid' => 3,
      'roles' => ['test_role' => 'test_role'],
    ]);
    return [
      '#markup' => new FormattableMarkup('User @id can administer modules.', ['@id' => $user3->id()]),
      '#access' => $user3->hasPermission('administer modules'),
    ];
  }

}
