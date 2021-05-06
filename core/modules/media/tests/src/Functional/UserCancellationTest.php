<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\Media;
use Drupal\user\CancellationHandlerInterface;

/**
 * Tests how media items react to user cancellation.
 */
class UserCancellationTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests how media items react to user cancellation.
   */
  public function testUserCancellation(): void {
    $media_type = $this->createMediaType('test');
    $alice = $this->drupalCreateUser();
    $bob = $this->drupalCreateUser();

    $aliceMedia = Media::create([
      'bundle' => $media_type->id(),
      'uid' => $alice->id(),
    ]);
    $aliceMedia->save();
    $this->assertSame($alice->id(), $aliceMedia->getOwnerId());
    $this->assertTrue($aliceMedia->isPublished());

    $bobMedia = Media::create([
      'bundle' => $media_type->id(),
      'uid' => $bob->id(),
    ]);
    $bobMedia->save();
    $this->assertSame($bob->id(), $bobMedia->getOwnerId());
    $this->assertTrue($bobMedia->isPublished());

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/people');
    $page = $this->getSession()->getPage();
    $page->checkField('Update the user ' . $alice->getDisplayName());
    $page->selectFieldOption('action', 'user_cancel_user_action');
    $page->pressButton('Apply to selected items');
    $page->selectFieldOption('user_cancel_method', CancellationHandlerInterface::METHOD_BLOCK_UNPUBLISH);
    $page->pressButton('Cancel accounts');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The update has been performed.');
    $aliceMedia = Media::load($aliceMedia->id());
    $this->assertFalse($aliceMedia->isPublished());
    $this->assertSame($alice->id(), $aliceMedia->getOwnerId());

    $page->checkField('Update the user ' . $bob->getDisplayName());
    $page->selectFieldOption('action', 'user_cancel_user_action');
    $page->pressButton('Apply to selected items');
    $page->selectFieldOption('user_cancel_method', CancellationHandlerInterface::METHOD_REASSIGN);
    $page->pressButton('Cancel accounts');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The update has been performed.');
    $bobMedia = Media::load($bobMedia->id());
    $this->assertTrue($bobMedia->isPublished());
    $this->assertTrue($bobMedia->getOwner()->isAnonymous());
  }

}
