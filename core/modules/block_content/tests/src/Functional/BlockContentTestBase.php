<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

use Drupal\Tests\block_content\Traits\BlockContentCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Sets up block content types.
 */
abstract class BlockContentTestBase extends BrowserTestBase {

  use BlockContentCreationTrait;

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer blocks',
    'access block library',
    'administer block types',
    'administer block content',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content'];

  /**
   * Whether or not to auto-create the basic block type during setup.
   *
   * @var bool
   */
  protected $autoCreateBasicBlockType = TRUE;

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    if ($this->autoCreateBasicBlockType) {
      $this->createBlockContentType(['id' => 'basic'], TRUE);
    }

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_actions_block');
  }

}
