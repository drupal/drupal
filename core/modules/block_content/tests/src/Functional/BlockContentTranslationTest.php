<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\language\Traits\LanguageTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests block content translations.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class BlockContentTranslationTest extends BlockContentTestBase {

  use LanguageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    static::enableBundleTranslation('block_content', 'basic');
    static::createLanguageFromLangcode('es');
    // Rebuild the container so LanguageServiceProvider adds the path
    // processors.
    $this->rebuildContainer();
  }

  /**
   * Tests block access considers translation context.
   */
  public function testBlockContentTranslationAccess(): void {
    $block_content = $this->createBlockContent(save: FALSE);
    $block_content->set('body', ['value' => 'English block']);
    $block_content->setUnpublished();
    $block_content->save();

    $esTranslation = $block_content->addTranslation('es', $block_content->toArray());
    $esTranslation->set('body', ['value' => 'Spanish block']);
    $esTranslation->setPublished();
    $esTranslation->save();

    $this->placeBlock('block_content:' . $block_content->uuid());

    // English translation is unpublished, neither translation should display
    // on the english homepage.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains('English block');
    $this->assertSession()->pageTextNotContains('Spanish block');

    // Spanish translation is published, it should display on the spanish
    // homepage.
    $this->drupalGet('<front>', ['language' => ConfigurableLanguage::load('es')]);
    $this->assertSession()->pageTextNotContains('English block');
    $this->assertSession()->pageTextContains('Spanish block');
  }

}
