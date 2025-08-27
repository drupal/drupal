<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the CKEditor version.
 *
 * @internal
 */
#[Group('ckeditor5')]
class VersionTest extends UnitTestCase {

  /**
   * Ensure that CKEditor5 versions are aligned.
   */
  public function testVersionAlignment(): void {
    $package_json = json_decode(file_get_contents(__DIR__ . '/../../../../../package.json'), TRUE);
    $ckeditor_dependencies = array_filter($package_json['devDependencies'], fn ($key) => str_starts_with($key, '@ckeditor/ckeditor5-'), ARRAY_FILTER_USE_KEY);
    $this->assertCount(1, array_unique($ckeditor_dependencies));
  }

}
