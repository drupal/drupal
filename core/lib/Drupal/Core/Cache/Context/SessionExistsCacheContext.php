<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the SessionExistsCacheContext service, for "session or not" caching.
 *
 * Cache context ID: 'session.exists'.
 */
class SessionExistsCacheContext implements CacheContextInterface {

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new SessionExistsCacheContext class.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(SessionConfigurationInterface $session_configuration, RequestStack $request_stack) {
    $this->sessionConfiguration = $session_configuration;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Session exists');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->sessionConfiguration->hasSession($this->requestStack->getCurrentRequest()) ? '1' : '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
