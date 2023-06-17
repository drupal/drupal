<?php

namespace Drupal\system;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for machine name transliteration routes.
 *
 * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/2662330
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
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3367037', E_USER_DEPRECATED);
    $text = $request->query->get('text');
    $langcode = $request->query->get('langcode');
    $replace_pattern = $request->query->get('replace_pattern');
    $replace_token = $request->query->get('replace_token');
    $replace = $request->query->get('replace');
    $lowercase = $request->query->get('lowercase');

    $transliterated = $this->transliteration->transliterate($text, $langcode, '_');
    if ($lowercase) {
      $transliterated = mb_strtolower($transliterated);
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
