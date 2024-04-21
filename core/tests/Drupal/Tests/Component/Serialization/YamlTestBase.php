<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Serialization;

use PHPUnit\Framework\TestCase;

/**
 * Provides standard data to validate different YAML implementations.
 */
abstract class YamlTestBase extends TestCase {

  /**
   * Some data that should be able to be serialized.
   */
  public static function providerEncodeDecodeTests() {
    return [
      [
        'foo' => 'bar',
        'id' => 'schnitzel',
        'ponies' => ['nope', 'thanks'],
        'how' => [
          'about' => 'if',
          'i' => 'ask',
          'nicely',
        ],
        'the' => [
          'answer' => [
            'still' => 'would',
            'be' => 'Y',
          ],
        ],
        'how_many_times' => 123,
        'should_i_ask' => FALSE,
        1,
        FALSE,
        [1, FALSE],
        [10],
        [0 => '123456'],
      ],
      [NULL],
    ];
  }

  /**
   * Some data that should be able to be deserialized.
   */
  public static function providerDecodeTests() {
    $data = [
      // NULL files.
      ['', NULL],
      ["\n", NULL],
      ["---\n...\n", NULL],

      // Node anchors.
      [
        "
jquery.ui:
  version: &jquery_ui 1.10.2

jquery.ui.accordion:
  version: *jquery_ui
",
        [
          'jquery.ui' => [
            'version' => '1.10.2',
          ],
          'jquery.ui.accordion' => [
            'version' => '1.10.2',
          ],
        ],
      ],
    ];

    // 1.2 Bool values.
    foreach (static::providerBoolTest() as $test) {
      $data[] = ['bool: ' . $test[0], ['bool' => $test[1]]];
    }
    $data = array_merge($data, static::providerBoolTest());

    return $data;
  }

  /**
   * Tests different boolean serialization and deserialization.
   */
  public static function providerBoolTest() {
    return [
      ['true', TRUE],
      ['TRUE', TRUE],
      ['True', TRUE],
      ['y', 'y'],
      ['Y', 'Y'],
      ['false', FALSE],
      ['FALSE', FALSE],
      ['False', FALSE],
      ['n', 'n'],
      ['N', 'N'],
    ];
  }

}
