<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\user\Traits\UserCancellationTrait;
use Drupal\user\CancellationHandlerInterface;

/**
 * Tests how file entities react to user cancellation.
 *
 * @group file
 */
class UserCancellationTest extends BrowserTestBase {

  use UserCancellationTrait;

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
    $charlie = $this->drupalCreateUser();

    $uri = uniqid('public://') . '.txt';
    file_put_contents($uri, $this->getRandomGenerator()->paragraphs());
    $this->assertFileExists($uri);
    $values = ['uri' => $uri];

    $aliceFile = File::create($values)->setOwner($alice);
    $aliceFile->save();

    $bobFile = File::create($values)->setOwner($bob);
    $bobFile->save();

    $charlieFile = File::create($values)->setOwner($charlie);
    $charlieFile->save();

    $this->drupalLogin($this->rootUser);
    $this->cancelUser($alice, CancellationHandlerInterface::METHOD_BLOCK_UNPUBLISH);
    $this->assertSame($alice->id(), File::load($aliceFile->id())->getOwnerId());
    $this->cancelUser($bob, CancellationHandlerInterface::METHOD_REASSIGN);
    $this->assertTrue(File::load($bobFile->id())->getOwner()->isAnonymous());
    $this->cancelUser($charlie, CancellationHandlerInterface::METHOD_DELETE);
    $this->assertNull(File::load($charlieFile->id()));
  }

}
