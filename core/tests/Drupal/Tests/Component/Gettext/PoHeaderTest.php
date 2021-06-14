<?php

namespace Drupal\Tests\Component\Gettext;

use Drupal\Component\Gettext\PoHeader;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Gettext PO file header handling features.
 *
 * @see Drupal\Component\Gettext\PoHeader.
 *
 * @group Gettext
 */
class PoHeaderTest extends TestCase {

  /**
   * Tests that plural expressions are evaluated correctly.
   *
   * Validate that the given plural expressions is evaluated with the correct
   * plural formula.
   *
   * @param string $plural
   *   The plural expression.
   * @param array $expected
   *   Array of expected plural positions keyed by plural value.
   *
   * @dataProvider providerTestPluralsFormula
   */
  public function testPluralsFormula($plural, $expected) {
    $p = new PoHeader();
    $parsed = $p->parsePluralForms($plural);
    list($nplurals, $new_plural) = $parsed;
    foreach ($expected as $number => $plural_form) {
      $result = isset($new_plural[$number]) ? $new_plural[$number] : $new_plural['default'];
      $this->assertEquals($result, $plural_form, 'Difference found at ' . $number . ': ' . $plural_form . ' versus ' . $result);
    }
  }

  /**
   * Data provider for testPluralsFormula.
   *
   * Gets pairs of plural expressions and expected plural positions keyed by
   * plural value.
   *
   * @return array
   *   Pairs of plural expressions and expected plural positions keyed by plural
   *   value.
   */
  public function providerTestPluralsFormula() {
    return [
      [
        'nplurals=1; plural=0;',
        ['default' => 0],
      ],
      [
        'nplurals=2; plural=(n > 1);',
        [0 => 0, 1 => 0, 'default' => 1],
      ],
      [
        'nplurals=2; plural=(n!=1);',
        [1 => 0, 'default' => 1],
      ],
      [
        'nplurals=2; plural=(((n==1)||((n%10)==1))?(0):1);',
        [
          1 => 0,
          11 => 0,
          21 => 0,
          31 => 0,
          41 => 0,
          51 => 0,
          61 => 0,
          71 => 0,
          81 => 0,
          91 => 0,
          101 => 0,
          111 => 0,
          121 => 0,
          131 => 0,
          141 => 0,
          151 => 0,
          161 => 0,
          171 => 0,
          181 => 0,
          191 => 0,
          'default' => 1,
        ],
      ],
      [
        'nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));',
        [
          1 => 0,
          2 => 1,
          3 => 1,
          4 => 1,
          21 => 0,
          22 => 1,
          23 => 1,
          24 => 1,
          31 => 0,
          32 => 1,
          33 => 1,
          34 => 1,
          41 => 0,
          42 => 1,
          43 => 1,
          44 => 1,
          51 => 0,
          52 => 1,
          53 => 1,
          54 => 1,
          61 => 0,
          62 => 1,
          63 => 1,
          64 => 1,
          71 => 0,
          72 => 1,
          73 => 1,
          74 => 1,
          81 => 0,
          82 => 1,
          83 => 1,
          84 => 1,
          91 => 0,
          92 => 1,
          93 => 1,
          94 => 1,
          101 => 0,
          102 => 1,
          103 => 1,
          104 => 1,
          121 => 0,
          122 => 1,
          123 => 1,
          124 => 1,
          131 => 0,
          132 => 1,
          133 => 1,
          134 => 1,
          141 => 0,
          142 => 1,
          143 => 1,
          144 => 1,
          151 => 0,
          152 => 1,
          153 => 1,
          154 => 1,
          161 => 0,
          162 => 1,
          163 => 1,
          164 => 1,
          171 => 0,
          172 => 1,
          173 => 1,
          174 => 1,
          181 => 0,
          182 => 1,
          183 => 1,
          184 => 1,
          191 => 0,
          192 => 1,
          193 => 1,
          194 => 1,
          'default' => 2,
        ],
      ],
      [
        'nplurals=3; plural=((n==1)?(0):(((n>=2)&&(n<=4))?(1):2));',
        [
          1 => 0,
          2 => 1,
          3 => 1,
          4 => 1,
          'default' => 2,
        ],
      ],
      [
        'nplurals=3; plural=((n==1)?(0):(((n==0)||(((n%100)>0)&&((n%100)<20)))?(1):2));',
        [
          0 => 1,
          1 => 0,
          2 => 1,
          3 => 1,
          4 => 1,
          5 => 1,
          6 => 1,
          7 => 1,
          8 => 1,
          9 => 1,
          10 => 1,
          11 => 1,
          12 => 1,
          13 => 1,
          14 => 1,
          15 => 1,
          16 => 1,
          17 => 1,
          18 => 1,
          19 => 1,
          101 => 1,
          102 => 1,
          103 => 1,
          104 => 1,
          105 => 1,
          106 => 1,
          107 => 1,
          108 => 1,
          109 => 1,
          110 => 1,
          111 => 1,
          112 => 1,
          113 => 1,
          114 => 1,
          115 => 1,
          116 => 1,
          117 => 1,
          118 => 1,
          119 => 1,
          'default' => 2,
        ],
      ],
      [
        'nplurals=3; plural=((n==1)?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));',
        [
          1 => 0,
          2 => 1,
          3 => 1,
          4 => 1,
          22 => 1,
          23 => 1,
          24 => 1,
          32 => 1,
          33 => 1,
          34 => 1,
          42 => 1,
          43 => 1,
          44 => 1,
          52 => 1,
          53 => 1,
          54 => 1,
          62 => 1,
          63 => 1,
          64 => 1,
          72 => 1,
          73 => 1,
          74 => 1,
          82 => 1,
          83 => 1,
          84 => 1,
          92 => 1,
          93 => 1,
          94 => 1,
          102 => 1,
          103 => 1,
          104 => 1,
          122 => 1,
          123 => 1,
          124 => 1,
          132 => 1,
          133 => 1,
          134 => 1,
          142 => 1,
          143 => 1,
          144 => 1,
          152 => 1,
          153 => 1,
          154 => 1,
          162 => 1,
          163 => 1,
          164 => 1,
          172 => 1,
          173 => 1,
          174 => 1,
          182 => 1,
          183 => 1,
          184 => 1,
          192 => 1,
          193 => 1,
          194 => 1,
          'default' => 2,
        ],
      ],
      [
        'nplurals=4; plural=(((n==1)||(n==11))?(0):(((n==2)||(n==12))?(1):(((n>2)&&(n<20))?(2):3)));',
        [
          1 => 0,
          2 => 1,
          3 => 2,
          4 => 2,
          5 => 2,
          6 => 2,
          7 => 2,
          8 => 2,
          9 => 2,
          10 => 2,
          11 => 0,
          12 => 1,
          13 => 2,
          14 => 2,
          15 => 2,
          16 => 2,
          17 => 2,
          18 => 2,
          19 => 2,
          'default' => 3,
        ],
      ],
      [
        'nplurals=4; plural=(((n%100)==1)?(0):(((n%100)==2)?(1):((((n%100)==3)||((n%100)==4))?(2):3)));',
        [
          1 => 0,
          2 => 1,
          3 => 2,
          4 => 2,
          101 => 0,
          102 => 1,
          103 => 2,
          104 => 2,
          'default' => 3,
        ],
      ],
      [
        'nplurals=5; plural=((n==1)?(0):((n==2)?(1):((n<7)?(2):((n<11)?(3):4))));',
        [
          0 => 2,
          1 => 0,
          2 => 1,
          3 => 2,
          4 => 2,
          5 => 2,
          6 => 2,
          7 => 3,
          8 => 3,
          9 => 3,
          10 => 3,
          'default' => 4,
        ],
      ],
      [
        'nplurals=6; plural=((n==1)?(0):((n==0)?(1):((n==2)?(2):((((n%100)>=3)&&((n%100)<=10))?(3):((((n%100)>=11)&&((n%100)<=99))?(4):5)))));',
        [
          0 => 1,
          1 => 0,
          2 => 2,
          3 => 3,
          4 => 3,
          5 => 3,
          6 => 3,
          7 => 3,
          8 => 3,
          9 => 3,
          10 => 3,
          100 => 5,
          101 => 5,
          102 => 5,
          103 => 3,
          104 => 3,
          105 => 3,
          106 => 3,
          107 => 3,
          108 => 3,
          109 => 3,
          110 => 3,
          'default' => 4,
        ],
      ],
    ];
  }

}
