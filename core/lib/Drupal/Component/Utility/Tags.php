<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Tags.
 */

namespace Drupal\Component\Utility;

/**
 * Defines a class that can explode and implode tags.
 */
class Tags {

  /**
   * Explodes a string of tags into an array.
   *
   * @param string $tags
   *   A string to explode.
   *
   * @return array
   *   An array of tags.
   */
  public static function explode($tags) {
    // This regexp allows the following types of user input:
    // this, "somecompany, llc", "and ""this"" w,o.rks", foo bar
    $regexp = '%(?:^|,\ *)("(?>[^"]*)(?>""[^"]* )*"|(?: [^",]*))%x';
    preg_match_all($regexp, $tags, $matches);
    $typed_tags = array_unique($matches[1]);

    $tags = array();
    foreach ($typed_tags as $tag) {
      // If a user has escaped a term (to demonstrate that it is a group,
      // or includes a comma or quote character), we remove the escape
      // formatting so to save the term into the database as the user intends.
      $tag = trim(str_replace('""', '"', preg_replace('/^"(.*)"$/', '\1', $tag)));
      if ($tag != "") {
        $tags[] = $tag;
      }
    }

    return $tags;
  }

  /**
   * Encodes a tag string, taking care of special cases like commas and quotes.
   *
   * @param string $tag
   *   A tag string.
   *
   * @return string
   *   The encoded string.
   */
  public static function encode($tag) {
    if (strpos($tag, ',') !== FALSE || strpos($tag, '"') !== FALSE) {
      return '"' . str_replace('"', '""', $tag) . '"';
    }
    return $tag;
  }

  /**
   * Implodes an array of tags into a string.
   *
   * @param array $tags
   *   An array of tags.
   *
   * @return string
   *   The imploded string.
   */
  public static function implode($tags) {
    $encoded_tags = array();
    foreach ($tags as $tag) {
      $encoded_tags[] = self::encode($tag);
    }
    return implode(', ', $encoded_tags);
  }

}
