<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\DrupalKernel;

use Drupal\Tests\BrowserTestBase;

// cspell:ignore msword silverlight

/**
 * Tests content negotiation.
 *
 * @group DrupalKernel
 */
class ContentNegotiationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Verifies HTML responses for bogus Accept headers.
   *
   * Drupal does not fully support older browsers, but a page output is still
   * expected.
   *
   * @see https://www.drupal.org/node/1716790
   */
  public function testBogusAcceptHeader(): void {
    $tests = [
      // See https://bugs.webkit.org/show_bug.cgi?id=27267.
      'Firefox 3.5 (2009)' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'IE8 (2009)' => 'image/gif, image/jpeg, image/pjpeg, image/pjpeg, application/x-shockwave-flash, application/xaml+xml, application/vnd.ms-xpsdocument, application/x-ms-xbap, application/x-ms-application, application/vnd.ms-excel, application/vnd.ms-powerpoint, application/msword, application/x-silverlight, */*',
      'Opera (2009)' => 'text/html, application/xml;q=0.9, application/xhtml+xml, image/png, image/jpeg, image/gif, image/x-xbitmap, */*;q=0.1',
      'Chrome (2009)' => 'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
      // See https://github.com/symfony/symfony/pull/564.
      'Firefox 3.6 (2010)' => 'text/html,application/xhtml+xml,application/json,application/xml;q=0.9,*/*;q=0.8',
      'Safari (2010)' => '*/*',
      'Opera (2010)' => 'image/jpeg,image/gif,image/x-xbitmap,text/html,image/webp,image/png,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.1',
      // See https://www.drupal.org/node/1716790.
      'Safari (2010), iOS 4.2.1 (2012)' => 'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
      'Android #1 (2012)' => 'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
      'Android #2 (2012)' => 'text/xml,text/html,application/xhtml+xml,image/png,text/plain,*/*;q=0.8',
    ];
    foreach ($tests as $case => $header) {
      $this->drupalGet('', [], ['Accept' => $header]);
      $this->assertSession()->pageTextNotContains('Unsupported Media Type');
      $this->assertSession()->pageTextContains('Log in');
    }
  }

}
