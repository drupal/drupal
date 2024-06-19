<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel\Migrate\d6;

use Drupal\language\ConfigurableLanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * @group migrate_drupal_6
 */
class MigrateLanguageTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * Asserts various properties of a configurable language entity.
   *
   * @param string $id
   *   The language ID.
   * @param string $label
   *   The language name.
   * @param string $direction
   *   (optional) The language's direction (one of the DIRECTION_* constants in
   *   ConfigurableLanguageInterface). Defaults to LTR.
   * @param int $weight
   *   (optional) The weight of the language. Defaults to 0.
   *
   * @internal
   */
  protected function assertLanguage(string $id, string $label, string $direction = ConfigurableLanguageInterface::DIRECTION_LTR, int $weight = 0): void {
    /** @var \Drupal\language\ConfigurableLanguageInterface $language */
    $language = ConfigurableLanguage::load($id);
    $this->assertInstanceOf(ConfigurableLanguageInterface::class, $language);
    $this->assertSame($label, $language->label());
    $this->assertSame($direction, $language->getDirection());
    $this->assertSame(0, $language->getWeight());
    $this->assertFalse($language->isLocked());
  }

  /**
   * Tests migration of Drupal 6 languages to configurable language entities.
   */
  public function testLanguageMigration(): void {
    $this->executeMigration('language');
    $this->assertLanguage('en', 'English');
    $this->assertLanguage('fr', 'French');
  }

}
