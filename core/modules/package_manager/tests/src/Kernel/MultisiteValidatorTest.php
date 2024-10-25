<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\MultisiteValidator
 * @group package_manager
 * @internal
 */
class MultisiteValidatorTest extends PackageManagerKernelTestBase {

  /**
   * Data provider for testMultisite().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerMultisite(): array {
    return [
      'sites.php present and listing multiple sites' => [
        <<<'PHP'
<?php
// Site 1: the main site.
$sites['example.com'] = 'default';
// Site 2: the shop.
$sites['shop.example.com'] = 'shop';
PHP,
        [
          ValidationResult::createError([
            t('Drupal multisite is not supported by Package Manager.'),
          ]),
        ],
      ],
      'sites.php present and listing single site' => [
        <<<'PHP'
<?php
// Site 1: the main site.
$sites['example.com'] = 'default';
PHP,
        [],
      ],
      'sites.php present and listing multiple aliases for a single site' => [
        <<<'PHP'
<?php
// Site 1: the main site.
$sites['example.com'] = 'example';
// Alias for site 1!
$sites['example.dev'] = 'example';
PHP,
        [],
      ],
      'sites.php absent' => [
        NULL,
        [],
      ],
    ];
  }

  /**
   * Tests that Package Manager flags an error if run in a multisite.
   *
   * @param string|null $sites_php
   *   The sites.php contents to write, if any. If NULL, no sites.php will be
   *   created.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerMultisite
   */
  public function testMultisite(?string $sites_php, array $expected_results = []): void {
    if ($sites_php) {
      $project_root = $this->container->get(PathLocator::class)->getProjectRoot();
      file_put_contents($project_root . '/sites/sites.php', $sites_php);
    }
    $this->assertStatusCheckResults($expected_results);
    $this->assertResults($expected_results, PreCreateEvent::class);
  }

  /**
   * Tests that an error is flagged if run in a multisite during pre-apply.
   *
   * @param string|null $sites_php
   *   The sites.php contents to write, if any. If NULL, no sites.php will be
   *   created.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerMultisite
   */
  public function testMultisiteDuringPreApply(?string $sites_php, array $expected_results = []): void {
    $this->addEventTestListener(function () use ($sites_php): void {
      if ($sites_php) {
        $project_root = $this->container->get(PathLocator::class)->getProjectRoot();
        file_put_contents($project_root . '/sites/sites.php', $sites_php);
      }
    });
    $this->assertResults($expected_results, PreApplyEvent::class);
  }

}
