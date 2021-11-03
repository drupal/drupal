<?php

namespace Drupal\Tests\path\Unit\migrate\process\d6;

use Drupal\path\Plugin\migrate\process\d6\UrlAliasLanguage;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests error message from deprecated UrlAliasLanguage process plugin.
 *
 * @group path
 * @group legacy
 * @coversDefaultClass \Drupal\path\Plugin\migrate\process\d6\UrlAliasLanguage
 */
class UrlAliasLanguageDeprecatedTest extends MigrateProcessTestCase {

  /**
   * Tests legacy UrlAliasLanguage process plugin.
   */
  public function testUrlAliasLanguageDeprecation() {
    $this->expectDeprecation("Drupal\path\Plugin\migrate\process\d6\UrlAliasLanguage is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3219051");
    new UrlAliasLanguage([], 'test', []);
  }

}
