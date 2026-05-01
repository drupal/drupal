<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Page;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests default HTML metatags on a page.
 */
#[Group('Page')]
#[RunTestsInSeparateProcesses]
class DefaultMetatagsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests meta tags.
   */
  public function testMetaTag(): void {
    $this->drupalGet('');
    // Ensures that the charset metatag is on the page.
    $this->assertSession()->elementsCount('xpath', '//meta[@charset="utf-8"]', 1);

    // Ensure that the charset one is the first metatag.
    $result = $this->getSession()->getPage()->findAll('xpath', '//meta');
    $this->assertEquals('utf-8', (string) $result[0]->getAttribute('charset'));

    // Ensure that the icon is on the page.
    $this->assertSession()->elementsCount('xpath', '//link[@rel = "icon"]', 1);
  }

}
