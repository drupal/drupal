<?php

namespace Drupal\system\Tests\Installer;

/**
 * Tests that keeping English in a foreign language install works.
 *
 * @group Installer
 */
class InstallerTranslationMultipleLanguageKeepEnglishTest extends InstallerTranslationMultipleLanguageForeignTest {

  /**
   * Switch to the multilingual testing profile with English kept.
   *
   * @var string
   */
  protected $profile = 'testing_multilingual_with_english';

}
