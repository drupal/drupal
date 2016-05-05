<?php

namespace Drupal\system;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for machine name transliteration routes.
 */
class MachineNameController implements ContainerInjectionInterface {

  /**
   * The transliteration helper.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a MachineNameController object.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration helper.
   */
  public function __construct(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('transliteration')
    );
  }

  /**
   * Transliterates a string in given language. Various postprocessing possible.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The input string and language for the transliteration.
   *   Optionally may contain the replace_pattern, replace, lowercase parameters.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The transliterated string.
   */
  public function transliterate(Request $request) {
    $text = $request->query->get('text');
    $langcode = $request->query->get('langcode');
    $replace_pattern = $request->query->get('replace_pattern');
    $replace = $request->query->get('replace');
    $lowercase = $request->query->get('lowercase');

    $transliterated = $this->transliteration->transliterate($text, $langcode, '_');
    if ($lowercase) {
      $transliterated = Unicode::strtolower($transliterated);
    }
    if (isset($replace_pattern) && isset($replace)) {
      // Quote the pattern delimiter and remove null characters to avoid the e
      // or other modifiers being injected.
      $transliterated = preg_replace('@' . strtr($replace_pattern, ['@' => '\@', chr(0) => '']) . '@', $replace, $transliterated);
    }
    return new JsonResponse($transliterated);
  }

}
