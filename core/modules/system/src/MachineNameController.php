<?php

namespace Drupal\system;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
   * The token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $tokenGenerator;

  /**
   * Constructs a MachineNameController object.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration helper.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $token_generator
   *   The token generator.
   */
  public function __construct(TransliterationInterface $transliteration, CsrfTokenGenerator $token_generator) {
    $this->transliteration = $transliteration;
    $this->tokenGenerator = $token_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('transliteration'),
      $container->get('csrf_token')
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
    $replace_token = $request->query->get('replace_token');
    $replace = $request->query->get('replace');
    $lowercase = $request->query->get('lowercase');

    $transliterated = $this->transliteration->transliterate($text, $langcode, '_');
    if ($lowercase) {
      $transliterated = Unicode::strtolower($transliterated);
    }

    if (isset($replace_pattern) && isset($replace)) {
      if (!isset($replace_token)) {
        throw new AccessDeniedHttpException("Missing 'replace_token' query parameter.");
      }
      elseif (!$this->tokenGenerator->validate($replace_token, $replace_pattern)) {
        throw new AccessDeniedHttpException("Invalid 'replace_token' query parameter.");
      }

      // Quote the pattern delimiter and remove null characters to avoid the e
      // or other modifiers being injected.
      $transliterated = preg_replace('@' . strtr($replace_pattern, ['@' => '\@', chr(0) => '']) . '@', $replace, $transliterated);
    }
    return new JsonResponse($transliterated);
  }

}
