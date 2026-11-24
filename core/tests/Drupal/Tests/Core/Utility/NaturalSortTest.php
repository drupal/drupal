<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Utility\NaturalSort;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

// cspell:ignore Émission

/**
 * Tests Drupal\Core\Utility\NaturalSort.
 */
#[CoversClass(NaturalSort::class)]
#[Group('Utility')]
class NaturalSortTest extends UnitTestCase {

  /**
   * Tests comparing strings with an explicit language code.
   */
  #[RequiresPhpExtension('intl')]
  public function testExplicitLangcode(): void {
    // Numbers embedded in strings are compared by their numeric value.
    $this->assertGreaterThan(0, NaturalSort::strnatcasecmp('Format 100x100', 'Format 10x10', 'en'));
    $this->assertLessThan(0, NaturalSort::strnatcasecmp('Format 10x10', 'Format 100x100', 'en'));
    $this->assertSame(0, NaturalSort::strnatcasecmp('Format 10x10', 'Format 10x10', 'en'));

    // Accented characters are sorted by their base letter, unlike
    // strnatcasecmp(), which sorts them after all ASCII characters.
    $this->assertLessThan(0, NaturalSort::strnatcasecmp('Émission', 'Format', 'en'));
    $this->assertGreaterThan(0, NaturalSort::strnatcasecmp('Format', 'Émission', 'en'));

    // Comparing is case-insensitive for different letters.
    $this->assertLessThan(0, NaturalSort::strnatcasecmp('apple', 'Banana', 'en'));
    $this->assertGreaterThan(0, NaturalSort::strnatcasecmp('Banana', 'apple', 'en'));
  }

  /**
   * Tests that the current language is used when no language code is given.
   */
  #[RequiresPhpExtension('intl')]
  public function testCurrentLanguage(): void {
    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->expects($this->atLeastOnce())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'fr']));
    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager);
    \Drupal::setContainer($container);

    $this->assertLessThan(0, NaturalSort::strnatcasecmp('Émission', 'Format'));
    $this->assertGreaterThan(0, NaturalSort::strnatcasecmp('Format 100x100', 'Format 10x10'));
  }

  /**
   * Tests the fallback to strnatcasecmp() when no language is available.
   */
  public function testNoLanguageFallback(): void {
    // Without a language code and without a language manager service the
    // comparison falls back to strnatcasecmp(), which compares bytes and
    // sorts accented characters after all ASCII characters.
    $this->assertGreaterThan(0, NaturalSort::strnatcasecmp('Émission', 'Format'));
    $this->assertLessThan(0, NaturalSort::strnatcasecmp('Format', 'Émission'));
    $this->assertGreaterThan(0, NaturalSort::strnatcasecmp('Format 100x100', 'Format 10x10'));
  }

}
