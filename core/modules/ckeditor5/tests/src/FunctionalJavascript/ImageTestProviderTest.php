<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image.
 *
 * @internal
 */
#[CoversClass(Image::class)]
#[Group('ckeditor5')]
#[Group('#slow')]
class ImageTestProviderTest extends ImageTestTestBase {
  use ImageTestProviderTrait;

}
