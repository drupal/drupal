<?php

namespace Drupal\Tests\system\Unit\Theme;

use Drupal\Core\Theme\Manifest;
use Drupal\Tests\UnitTestCase;

/**
 * Tests manifest file.
 *
 * @group Theme
 * @coversDefaultClass \Drupal\Core\Theme\Manifest
 */
class ManifestTest extends UnitTestCase {

  /**
   * @covers ::overwriteWithNewData
   */
  public function testOverwrite() {
    $data = ['owl' => 'Can fly'];
    $sut = new Manifest($data);
    $this->assertEquals($data, $sut->toArray());

    $new_data = ['llama' => 'Can not fly'];
    $overwritten = $sut->overwriteWithNewData($new_data);
    $this->assertEquals($data, $sut->toArray());
    $this->assertEquals($new_data, $overwritten->toArray());
  }

}
