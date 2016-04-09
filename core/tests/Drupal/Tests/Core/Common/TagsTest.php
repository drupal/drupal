<?php

namespace Drupal\Tests\Core\Common;

use Drupal\Component\Utility\Tags;
use Drupal\Tests\UnitTestCase;

/**
 * Tests explosion and implosion of autocomplete tags.
 *
 * @group Common
 */
class TagsTest extends UnitTestCase {

  protected $validTags = array(
    'Drupal' => 'Drupal',
    'Drupal with some spaces' => 'Drupal with some spaces',
    '"Legendary Drupal mascot of doom: ""Druplicon"""' => 'Legendary Drupal mascot of doom: "Druplicon"',
    '"Drupal, although it rhymes with sloopal, is as awesome as a troopal!"' => 'Drupal, although it rhymes with sloopal, is as awesome as a troopal!',
  );

  /**
   * Explodes a series of tags.
   */
  public function explodeTags() {
    $string = implode(', ', array_keys($this->validTags));
    $tags = Tags::explode($string);
    $this->assertTags($tags);
  }

  /**
   * Implodes a series of tags.
   */
  public function testImplodeTags() {
    $tags = array_values($this->validTags);
    // Let's explode and implode to our heart's content.
    for ($i = 0; $i < 10; $i++) {
      $string = Tags::implode($tags);
      $tags = Tags::explode($string);
    }
    $this->assertTags($tags);
  }

  /**
   * Helper function: asserts that the ending array of tags is what we wanted.
   */
  protected function assertTags($tags) {
    $original = $this->validTags;
    foreach ($tags as $tag) {
      $key = array_search($tag, $original);
      $this->assertTrue((bool) $key, $tag, sprintf('Make sure tag %s shows up in the final tags array (originally %s)', $tag, $key));
      unset($original[$key]);
    }
    foreach ($original as $leftover) {
      $this->fail(sprintf('Leftover tag %s was left over.', $leftover));
    }
  }

}
