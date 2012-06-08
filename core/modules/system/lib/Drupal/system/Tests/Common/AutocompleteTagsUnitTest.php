<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\AutocompleteTagsUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests drupal_explode_tags() and drupal_implode_tags().
 */
class AutocompleteTagsUnitTest extends UnitTestBase {
  var $validTags = array(
    'Drupal' => 'Drupal',
    'Drupal with some spaces' => 'Drupal with some spaces',
    '"Legendary Drupal mascot of doom: ""Druplicon"""' => 'Legendary Drupal mascot of doom: "Druplicon"',
    '"Drupal, although it rhymes with sloopal, is as awesome as a troopal!"' => 'Drupal, although it rhymes with sloopal, is as awesome as a troopal!',
  );

  public static function getInfo() {
    return array(
      'name' => 'Autocomplete tags',
      'description' => 'Tests explosion and implosion of autocomplete tags.',
      'group' => 'Common',
    );
  }

  /**
   * Explode a series of tags.
   */
  function testDrupalExplodeTags() {
    $string = implode(', ', array_keys($this->validTags));
    $tags = drupal_explode_tags($string);
    $this->assertTags($tags);
  }

  /**
   * Implode a series of tags.
   */
  function testDrupalImplodeTags() {
    $tags = array_values($this->validTags);
    // Let's explode and implode to our heart's content.
    for ($i = 0; $i < 10; $i++) {
      $string = drupal_implode_tags($tags);
      $tags = drupal_explode_tags($string);
    }
    $this->assertTags($tags);
  }

  /**
   * Helper function: asserts that the ending array of tags is what we wanted.
   */
  function assertTags($tags) {
    $original = $this->validTags;
    foreach ($tags as $tag) {
      $key = array_search($tag, $original);
      $this->assertTrue($key, t('Make sure tag %tag shows up in the final tags array (originally %original)', array('%tag' => $tag, '%original' => $key)));
      unset($original[$key]);
    }
    foreach ($original as $leftover) {
      $this->fail(t('Leftover tag %leftover was left over.', array('%leftover' => $leftover)));
    }
  }
}
