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
   * Constructs a new SessionExistsCacheContext class.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $sessionConfiguration
   *   The session configuration.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected SessionConfigurationInterface $sessionConfiguration,
    protected RequestStack $requestStack,
  ) {
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
