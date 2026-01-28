<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'username' theme element.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
class UserNameThemeCacheTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Test cache metadata when rendering arrays using 'username' theme element.
   */
  public function testUserNameThemeRenderCache(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $context = new RenderContext();
    // First, do not set #account, to test for label to anonymous.
    $build = [
      '#theme' => 'username',
    ];
    $markup = $renderer->executeInRenderContext($context, fn() => $renderer->render($build));
    $this->assertSame('<span></span>', (string) $markup);
    $metadata = $context->pop();
    $this->assertInstanceOf(CacheableDependencyInterface::class, $metadata);
    $this->assertEmpty($metadata->getCacheContexts());
    $this->assertEmpty($metadata->getCacheTags());
    $this->assertSame(-1, $metadata->getCacheMaxAge());

    $user1 = User::create([
      'name' => 'user 1',
      'status' => TRUE,
    ]);
    $user1->save();
    $user2 = User::create([
      'name' => 'user 2',
      'status' => FALSE,
    ]);
    $user2->save();

    // Test that viewing label to own profile or viewing label to a different user's
    // profile is the same without 'access user profiles' permission.
    foreach ([$user1, $user2] as $viewing_user) {
      $this->setCurrentUser($viewing_user);
      $other_user = $viewing_user === $user1 ? $user1 : $user2;
      foreach ([$viewing_user, $other_user] as $viewed_user) {
        $build = [
          '#theme' => 'username',
          '#account' => $viewed_user,
        ];

        $markup = $renderer->executeInRenderContext($context, fn() => $renderer->render($build));
        $this->assertSame("<span>{$viewed_user->getDisplayName()}</span>", (string) $markup);
        $metadata = $context->pop();
        $this->assertInstanceOf(CacheableDependencyInterface::class, $metadata);
        $cacheContexts = $metadata->getCacheContexts();
        $this->assertSame(['user.permissions'], $cacheContexts);
        $this->assertSame(["user:{$viewed_user->id()}"], $metadata->getCacheTags());
        $this->assertSame(-1, $metadata->getCacheMaxAge());
      }
    }

    // Now test viewing username labels with a user who can view user profiles.
    $this->setUpCurrentUser(['name' => 'user who can view profiles'], ['access user profiles']);
    $build = [
      '#theme' => 'username',
      '#account' => $user1,
    ];
    // User 1 is active, so there should be a link to the profile.
    $markup = $renderer->executeInRenderContext($context, fn() => $renderer->render($build));
    $this->assertSame('<a title="View user profile." href="/user/1">user 1</a>', (string) $markup);
    $metadata = $context->pop();
    $this->assertInstanceOf(CacheableDependencyInterface::class, $metadata);
    $this->assertSame(['user.permissions'], $metadata->getCacheContexts());
    $this->assertSame(['user:1'], $metadata->getCacheTags());
    $this->assertSame(-1, $metadata->getCacheMaxAge());

    $build = [
      '#theme' => 'username',
      '#account' => $user2,
    ];
    // User 2 is inactive, so no link.
    $markup = $renderer->executeInRenderContext($context, fn() => $renderer->render($build));
    $this->assertSame('<span>user 2</span>', (string) $markup);
    $metadata = $context->pop();
    $this->assertInstanceOf(CacheableDependencyInterface::class, $metadata);
    $this->assertSame(['user.permissions'], $metadata->getCacheContexts());
    $this->assertSame(['user:2'], $metadata->getCacheTags());
    $this->assertSame(-1, $metadata->getCacheMaxAge());
  }

}
