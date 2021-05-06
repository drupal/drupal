<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\CancellationHandlerInterface;

/**
 * Tests how file entities react to user cancellation.
 *
 * @group file
 */
class UserCancellationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'views',
  ];

  /**
   * Tests how nodes react to user cancellation.
   */
  public function testUserCancellation(): void {
    $alice = $this->drupalCreateUser();
    $bob = $this->drupalCreateUser();

    $uri = uniqid('public://') . '.txt';
    file_put_contents($uri, $this->getRandomGenerator()->paragraphs());
    $this->assertFileExists($uri);

    $aliceFile = File::create(['uri' => $uri])->setOwner($alice);
    $aliceFile->save();

    $bobFile = File::create(['uri' => $uri])->setOwner($bob);
    $bobFile->save();

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
    $this->assertSame($alice->id(), File::load($aliceFile->id())->getOwnerId());

    $page->checkField('Update the user ' . $bob->getDisplayName());
    $page->selectFieldOption('action', 'user_cancel_user_action');
    $page->pressButton('Apply to selected items');
    $page->selectFieldOption('user_cancel_method', CancellationHandlerInterface::METHOD_REASSIGN);
    $page->pressButton('Cancel accounts');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The update has been performed.');
    $this->assertTrue(File::load($bobFile->id())->getOwner()->isAnonymous());
  }

}
