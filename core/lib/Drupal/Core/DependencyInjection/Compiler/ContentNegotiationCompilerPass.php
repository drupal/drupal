<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Provides a compiler pass which configures content negotiation headers.
 *
 * @see core.services.yml
 */
class ContentNegotiationCompilerPass implements CompilerPassInterface {

  /**
   * The default header settings.
   *
   * @var array
   */
  protected static $defaultHeaderSettings = [
    'accept' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasParameter('content_negotiation.config')) {
      $content_negotiation_config = [
        'enabled' => Settings::get('enable_content_negotiation', FALSE),
        'headers' => static::$defaultHeaderSettings,
      ];
    }
    else {
      $content_negotiation_config = $container->getParameter('content_negotiation.config');
      // Since RFC 7540 (HTTP/2), header names ought to be lower-cased.
      // @see https://tools.ietf.org/html/rfc7540#section-8.1.2
      $content_negotiation_config['headers'] = array_reduce(array_keys($content_negotiation_config['headers'] ?? []), function ($respect_headers, $header_name) use ($content_negotiation_config) {
        $respect_headers[strtolower($header_name)] = $content_negotiation_config['headers'][$header_name];
        return $respect_headers;
      }, static::$defaultHeaderSettings);
    }
    // If Drupal cannot respond with alternate serialization formats, then it
    // does not make sense to enable content negotiation via the `accept` header.
    // This will cause unnecessary cache variation.
    $content_negotiation_config['headers']['accept'] = $content_negotiation_config['headers']['accept'] && $container->hasParameter('serializer.formats');
    $container->setParameter('content_negotiation.config', $content_negotiation_config);
  }

}
