<?php

namespace Drupal\csrf_test\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Deprecated CSRF token routes.
 *
 * This controller tests using the deprecated CSRF token key 'rest'.
 *
 * @todo This class can be removed in 8.3.
 *
 * @see \Drupal\Core\Access\CsrfRequestHeaderAccessCheck::access()
 */
class DeprecatedCsrfTokenController implements ContainerInjectionInterface {

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
   * Returns a CSRF using the deprecated 'rest' value protecting session token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function csrfToken() {
    return new Response($this->tokenGenerator->get('rest'), 200, ['Content-Type' => 'text/plain']);
  }

}
