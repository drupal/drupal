<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests active link JS behavior.
 *
 * @see Drupal.behaviors.activeLinks
 */
#[Group('system')]
#[RunTestsInSeparateProcesses]
class ActiveLinkTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures no JS error is thrown with query strings containing special chars.
   */
  public function testQueryStringQuotes(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);
    $this->drupalGet($this->getSession()->getCurrentUrl(), ['query' => ['foo' => "\"'[](){}*+~>|\\/:;,.!@#$%^&-_=?<>"]]);
    $this->failOnJavaScriptErrors();
  }

}
