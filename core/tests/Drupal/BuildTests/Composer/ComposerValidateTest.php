<?php

declare(strict_types=1);

namespace Drupal\BuildTests\Composer;

use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\Tests\Composer\ComposerIntegrationTrait;

/**
 * @group Composer
 */
class ComposerValidateTest extends BuildTestBase {

  use ComposerIntegrationTrait;

  public static function provideComposerJson() {
    $data = [];
    $composer_json_finder = self::getComposerJsonFinder(self::getDrupalRootStatic());
    foreach ($composer_json_finder->getIterator() as $composer_json) {
      $data[] = [$composer_json->getPathname()];
    }
    return $data;
  }

  /**
   * @dataProvider provideComposerJson
   */
  public function testValidateComposer($path): void {
    $this->executeCommand('composer validate --strict --no-check-all ' . $path);
    $this->assertCommandSuccessful();
  }

}
