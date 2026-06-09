<?php

declare(strict_types=1);

// cSpell:ignore PHPGGC's, phpggc

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests protection against Drupal/FI1 gadget chain.
 */
#[Group('File')]
#[RunTestsInSeparateProcesses]
class FileIncludeGadgetChainTest extends KernelTestBase {

  /**
   * Provider for paths to include.
   *
   * @return array
   *   Paths for test files to try and include with the gadget chain.
   */
  public static function providerIncludePaths(): array {
    return [
      ['public://cuckoo.txt'],
      ['PUBLIC_PLACEHOLDER/cuckoo.jpg'],
    ];
  }

  /**
   * Tests unserializing a Drupal/FI1 payload.
   */
  #[DataProvider('providerIncludePaths')]
  #[IgnoreDeprecations]
  public function testFileDeleteGadgetChain(string $path): void {
    $path = str_replace('PUBLIC_PLACEHOLDER', Settings::get('file_public_path'), $path);
    // cspell:disable-next-line
    file_put_contents($path, "<?php print base64_decode('dGhpcyBzaG91bGQgbm90IGJl');?>");
    $output = '';
    // ./phpggc Drupal/FI1 public://canary.txt
    $payload = 'O:36:"Drupal\views\DisplayPluginCollection":1:{s:15:"pluginInstances";a:1:{i:0;O:36:"Drupal\Core\Extension\ProceduralCall":1:{s:8:"includes";a:1:{s:7:"destroy";PATH_PLACEHOLDER;}}}}';
    // e.g. s:19:"public://cuckoo.txt"
    $payload = str_replace('PATH_PLACEHOLDER', 's:' . strlen($path) . ':"' . $path . '"', $payload);
    ob_start();
    try {
      unserialize($payload);
    }
    catch (\Throwable) {
      // Error: Call to undefined function \destroy()
      // BadMethodCallException: Cannot unserialize Drupal\Core\Extension\ProceduralCall
    }
    $output = ob_get_clean();
    $this->assertNotEquals('this should not be', $output);
  }

}
