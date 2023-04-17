<?php

namespace Drupal\Core\Security\Attribute;

/**
 * Attribute to tell that a method is a trusted callback.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class TrustedCallback {}
