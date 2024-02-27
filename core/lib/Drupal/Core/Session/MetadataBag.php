<?php

namespace Drupal\Core\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag as SymfonyMetadataBag;

/**
 * Provides a container for application specific session metadata.
 */
class MetadataBag extends SymfonyMetadataBag {

  /**
   * The key used to store the CSRF token seed in the session.
   */
  const CSRF_TOKEN_SEED = 's';

  /**
   * Constructs a new metadata bag instance.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   */
  public function __construct(Settings $settings) {
    $update_threshold = $settings->get('session_write_interval', 180);
    parent::__construct('_sf2_meta', $update_threshold);
  }

  /**
   * Set the CSRF token seed.
   *
   * @param string $csrf_token_seed
   *   The per-session CSRF token seed.
   */
  public function setCsrfTokenSeed($csrf_token_seed) {
    $this->meta[static::CSRF_TOKEN_SEED] = $csrf_token_seed;
  }

  /**
   * Get the CSRF token seed.
   *
   * @return string|null
   *   The per-session CSRF token seed or null when no value is set.
   */
  public function getCsrfTokenSeed() {
    if (isset($this->meta[static::CSRF_TOKEN_SEED])) {
      return $this->meta[static::CSRF_TOKEN_SEED];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function stampNew($lifetime = NULL): void {
    parent::stampNew($lifetime);

    // Set the token seed immediately to avoid a race condition between two
    // simultaneous requests without a seed.
    $this->setCsrfTokenSeed(Crypt::randomBytesBase64());
  }

}
