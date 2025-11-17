<?php

declare(strict_types=1);
namespace Drupal\Core\Template\Attribute;

/**
 * Allow twig access to methods.
 *
 * Twig "sandboxes" templates to prevent them from
 * - having unwanted side effects (like calling node.delete())
 * - getting access to information outside the sandbox
 * This access attribute must only be given to methods that can not break the
 * sandbox.
 *
 * Note that Twig is not only used in templating, but also as a templating and
 * configuration language in core (e.g. views) and custom modules, which makes
 * its power available to site builders and maybe even site users with proper
 * permissions.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class TwigAllowed {}
