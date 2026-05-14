<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\HttpKernelUiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verify the order of the help page.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class HelpPageOrderTest extends KernelTestBase {

  use HttpKernelUiHelperTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['help', 'help_page_test', 'system', 'user'];

  /**
   * Strings to search for on admin/help, in order.
   *
   * @var string[]
   */
  protected array $stringOrder = [
    'Module overviews are provided',
    'This description should appear',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    // Create and log in user.
    $account = $this->createUser([
      'access help pages',
    ]);
    $this->setCurrentUser($account);
  }

  /**
   * Tests the order of the help page.
   */
  public function testHelp(): void {
    $pos = 0;
    $this->drupalGet('admin/help');
    $page_text = $this->getTextContent();
    foreach ($this->stringOrder as $item) {
      $new_pos = strpos($page_text, $item, $pos);
      $this->assertGreaterThan($pos, $new_pos, "Order of $item is not correct on help page");
      $pos = $new_pos;
    }
  }

}
