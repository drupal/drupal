<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Common;

use Drupal\Component\Utility\Tags;
use Drupal\Tests\UnitTestCase;

// cspell:ignore sloopal troopal

/**
 * Tests explosion and implosion of autocomplete tags.
 *
 * @group Common
 */
class TagsTest extends UnitTestCase {

  protected $validTags = [
    'Drupal' => 'Drupal',
    'Drupal with some spaces' => 'Drupal with some spaces',
    '"Legendary Drupal mascot of doom: ""Druplicon"""' => 'Legendary Drupal mascot of doom: "Druplicon"',
    '"Drupal, although it rhymes with sloopal, is as awesome as a troopal!"' => 'Drupal, although it rhymes with sloopal, is as awesome as a troopal!',
  ];

  /**
   * Explodes a series of tags.
   */
  public function testExplodeTags(): void {
    $string = implode(', ', array_keys($this->validTags));
    $tags = Tags::explode($string);
    $this->assertEquals(array_values($this->validTags), $tags);
  }

  /**
   * Implodes a series of tags.
   */
  public function testImplodeTags(): void {
    $tags = array_values($this->validTags);
    // Let's explode and implode to our heart's content.
    for ($i = 0; $i < 10; $i++) {
      $string = Tags::implode($tags);
      $tags = Tags::explode($string);
    }
    $this->assertEquals(array_values($this->validTags), $tags);
  }

}
