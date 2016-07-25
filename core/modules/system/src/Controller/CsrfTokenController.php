<?php

namespace Drupal\system\Controller;

use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for CSRF token routes.
 */
class CsrfTokenController implements ContainerInjectionInterface {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $tokenGenerator;

  /**
   * Constructs a new CsrfTokenController object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $token_generator
   *   The CSRF token generator.
   */
  public function __construct(CsrfTokenGenerator $token_generator) {
    $this->tokenGenerator = $token_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token')
    );
  }

  /**
   * Returns a CSRF protecting session token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function csrfToken() {
    return new Response($this->tokenGenerator->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY), 200, ['Content-Type' => 'text/plain']);
  }

}
