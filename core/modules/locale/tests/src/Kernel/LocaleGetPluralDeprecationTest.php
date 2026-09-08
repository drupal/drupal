<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests deprecation of locale_get_plural().
 */
#[CoversFunction('locale_get_plural')]
#[Group('locale')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class LocaleGetPluralDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['locale'];

  /**
   * Tests deprecation of locale_get_plural().
   */
  public function testLocaleGetPlural(): void {
    $this->expectUserDeprecationMessage('locale_get_plural() is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3590542');
    // English has no imported translation, so the default formula is used.
    $this->assertSame(1, locale_get_plural(2));
  }

}
