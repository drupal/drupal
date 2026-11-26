<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\StringTranslation;

use Drupal\Component\Gettext\PoHeader;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the plural strings of core without a translation.
 */
#[Group('StringTranslation')]
#[RunTestsInSeparateProcesses]
class PluralTranslationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('locale', [
      'locales_location',
      'locales_source',
      'locales_target',
    ]);
    ConfigurableLanguage::createFromLangcode('ru')->save();
    $formula = 'nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));';
    $header = new PoHeader();
    [$nplurals, $formula] = $header->parsePluralForms($formula);
    \Drupal::service('locale.plural.formula')->setPluralFormula('ru', $nplurals, $formula);
  }

  /**
   * Tests a plural string of core without a translation.
   *
   * Without a translation, the English source is displayed, but using the
   * plural formula of the target language. In Russian, the first form, which is
   * the singular in English, is used for 21. If "1 byte" would be the singular,
   * "1 byte" would be displayed for 21 bytes.
   */
  public function testUntranslatedSingular(): void {
    $this->assertSame('1 byte', (string) ByteSizeMarkup::create(1, 'ru'));
    $this->assertSame('21 byte', (string) ByteSizeMarkup::create(21, 'ru'));
    $this->assertSame('22 bytes', (string) ByteSizeMarkup::create(22, 'ru'));
  }

}
