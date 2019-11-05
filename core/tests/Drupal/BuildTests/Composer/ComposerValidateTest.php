<?php

namespace Drupal\BuildTests\Composer;

use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\Tests\Composer\ComposerIntegrationTrait;

/**
 * @group Composer
 * @requires externalCommand composer
 */
class ComposerValidateTest extends BuildTestBase {

  use ComposerIntegrationTrait;

  public function provideComposerJson() {
    $data = [];
    $composer_json_finder = $this->getComposerJsonFinder($this->getDrupalRoot());
    foreach ($composer_json_finder->getIterator() as $composer_json) {
      $data[] = [$composer_json->getPathname()];
    }
    return $data;
  }

  /**
   * @dataProvider provideComposerJson
   */
  public function testValidateComposer($path) {
    $this->executeCommand('composer validate --strict --no-check-all ' . $path);
    $this->assertCommandSuccessful();
  }

}
