<?php

namespace Drupal\search;

/**
 * Processes search text for indexing.
 */
interface SearchTextProcessorInterface {

  /**
   * Matches all 'N' Unicode character classes (numbers)
   */
  const PREG_CLASS_NUMBERS =
    '\x{30}-\x{39}\x{b2}\x{b3}\x{b9}\x{bc}-\x{be}\x{660}-\x{669}\x{6f0}-\x{6f9}' .
    '\x{966}-\x{96f}\x{9e6}-\x{9ef}\x{9f4}-\x{9f9}\x{a66}-\x{a6f}\x{ae6}-\x{aef}' .
    '\x{b66}-\x{b6f}\x{be7}-\x{bf2}\x{c66}-\x{c6f}\x{ce6}-\x{cef}\x{d66}-\x{d6f}' .
    '\x{e50}-\x{e59}\x{ed0}-\x{ed9}\x{f20}-\x{f33}\x{1040}-\x{1049}\x{1369}-' .
    '\x{137c}\x{16ee}-\x{16f0}\x{17e0}-\x{17e9}\x{17f0}-\x{17f9}\x{1810}-\x{1819}' .
    '\x{1946}-\x{194f}\x{2070}\x{2074}-\x{2079}\x{2080}-\x{2089}\x{2153}-\x{2183}' .
    '\x{2460}-\x{249b}\x{24ea}-\x{24ff}\x{2776}-\x{2793}\x{3007}\x{3021}-\x{3029}' .
    '\x{3038}-\x{303a}\x{3192}-\x{3195}\x{3220}-\x{3229}\x{3251}-\x{325f}\x{3280}-' .
    '\x{3289}\x{32b1}-\x{32bf}\x{ff10}-\x{ff19}';

  /**
   * Matches all 'P' Unicode character classes (punctuation)
   */
  const PREG_CLASS_PUNCTUATION =
    '\x{21}-\x{23}\x{25}-\x{2a}\x{2c}-\x{2f}\x{3a}\x{3b}\x{3f}\x{40}\x{5b}-\x{5d}' .
    '\x{5f}\x{7b}\x{7d}\x{a1}\x{ab}\x{b7}\x{bb}\x{bf}\x{37e}\x{387}\x{55a}-\x{55f}' .
    '\x{589}\x{58a}\x{5be}\x{5c0}\x{5c3}\x{5f3}\x{5f4}\x{60c}\x{60d}\x{61b}\x{61f}' .
    '\x{66a}-\x{66d}\x{6d4}\x{700}-\x{70d}\x{964}\x{965}\x{970}\x{df4}\x{e4f}' .
    '\x{e5a}\x{e5b}\x{f04}-\x{f12}\x{f3a}-\x{f3d}\x{f85}\x{104a}-\x{104f}\x{10fb}' .
    '\x{1361}-\x{1368}\x{166d}\x{166e}\x{169b}\x{169c}\x{16eb}-\x{16ed}\x{1735}' .
    '\x{1736}\x{17d4}-\x{17d6}\x{17d8}-\x{17da}\x{1800}-\x{180a}\x{1944}\x{1945}' .
    '\x{2010}-\x{2027}\x{2030}-\x{2043}\x{2045}-\x{2051}\x{2053}\x{2054}\x{2057}' .
    '\x{207d}\x{207e}\x{208d}\x{208e}\x{2329}\x{232a}\x{23b4}-\x{23b6}\x{2768}-' .
    '\x{2775}\x{27e6}-\x{27eb}\x{2983}-\x{2998}\x{29d8}-\x{29db}\x{29fc}\x{29fd}' .
    '\x{3001}-\x{3003}\x{3008}-\x{3011}\x{3014}-\x{301f}\x{3030}\x{303d}\x{30a0}' .
    '\x{30fb}\x{fd3e}\x{fd3f}\x{fe30}-\x{fe52}\x{fe54}-\x{fe61}\x{fe63}\x{fe68}' .
    '\x{fe6a}\x{fe6b}\x{ff01}-\x{ff03}\x{ff05}-\x{ff0a}\x{ff0c}-\x{ff0f}\x{ff1a}' .
    '\x{ff1b}\x{ff1f}\x{ff20}\x{ff3b}-\x{ff3d}\x{ff3f}\x{ff5b}\x{ff5d}\x{ff5f}-' .
    '\x{ff65}';

  /**
   * Matches CJK (Chinese, Japanese, Korean) letter-like characters.
   *
   * This list is derived from the "East Asian Scripts" section of
   * http://www.unicode.org/charts/index.html, as well as a comment on
   * http://unicode.org/reports/tr11/tr11-11.html listing some character
   * ranges that are reserved for additional CJK ideographs.
   *
   * The character ranges do not include numbers, punctuation, or symbols, since
   * these are handled separately in search. Note that radicals and strokes are
   * considered symbols. (See
   * http://www.unicode.org/Public/UNIDATA/extracted/DerivedGeneralCategory.txt)
   *
   * @see \Drupal\search\SearchTextProcessor::expandCjk()
   */
  const PREG_CLASS_CJK =
    '\x{1100}-\x{11FF}\x{3040}-\x{309F}\x{30A1}-\x{318E}' .
    '\x{31A0}-\x{31B7}\x{31F0}-\x{31FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FCF}' .
    '\x{A000}-\x{A48F}\x{A4D0}-\x{A4FD}\x{A960}-\x{A97F}\x{AC00}-\x{D7FF}' .
    '\x{F900}-\x{FAFF}\x{FF21}-\x{FF3A}\x{FF41}-\x{FF5A}\x{FF66}-\x{FFDC}' .
    '\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}';

  /**
   * Processes text into words for indexing.
   *
   * @param string $text
   *   Text to process.
   * @param string|null $langcode
   *   Language code for the language of $text, if known.
   *
   * @return array
   *   Array of words in the simplified, preprocessed text.
   *
   * @see \Drupal\search\SearchTextProcessorInterface::analyze()
   */
  public function process(string $text, ?string $langcode = NULL): array;

  /**
   * Runs the text through character analyzers in preparation for indexing.
   *
   * Processing steps:
   * - Entities are decoded.
   * - Text is lower-cased and diacritics (accents) are removed.
   * - hook_search_preprocess() is invoked.
   * - CJK (Chinese, Japanese, Korean) characters are processed, depending on
   *   the search settings.
   * - Punctuation is processed (removed or replaced with spaces, depending on
   *   where it is; see code for details).
   * - Words are truncated to 50 characters maximum.
   *
   * @param string $text
   *   Text to simplify.
   * @param string|null $langcode
   *   (optional) Language code for the language of $text, if known.
   *
   * @return string
   *   Simplified and processed text.
   *
   * @see hook_search_preprocess()
   */
  public function analyze(string $text, ?string $langcode = NULL): string;

}
