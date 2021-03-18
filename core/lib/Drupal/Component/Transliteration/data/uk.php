<?php

/**
 * @file
 * Ukrainian transliteration data for the PhpTransliteration class.
 * @see https://zakon.rada.gov.ua/laws/show/55-2010-п#Text
 */

$overrides['uk'] = [
  0x413 => 'H',
  0x433 => 'h',
  0x404 => 'Ye',
  0x454 => 'ye',
  0x418 => 'Y',
  0x438 => 'y',
  0x407 => 'Yi',
  0x457 => 'yi',
  0x426 => 'Ts',
  0x446 => 'ts',
  0x429 => 'Shch',
  0x449 => 'shch',
  // Hide several variations of apostrophe character.
  0x2bc => '',
  0x27 => '',
  0x2019 => '',
];
