<?php

namespace Drupal\hal\LinkManager;

use Drupal\serialization\Normalizer\CacheableNormalizerInterface;

/**
 * Defines an abstract base-class for HAL link manager objects.
 */
abstract class LinkManagerBase {

  /**
   * Link domain used for type links URIs.
   *
   * @var string
   */
  protected $linkDomain;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function setLinkDomain($domain) {
    $this->linkDomain = rtrim($domain, '/');
    return $this;
  }

  /**
   * Gets the link domain.
   *
   * @param array $context
   *   Normalization/serialization context.
   *
   * @return string
   *   The link domain.
   *
   * @see \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   * @see \Symfony\Component\Serializer\SerializerInterface::serialize()
   * @see \Drupal\serialization\Normalizer\CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY
   */
  protected function getLinkDomain(array $context = []) {
    if (empty($this->linkDomain)) {
      if ($domain = $this->configFactory->get('hal.settings')->get('link_domain')) {
        // Bubble the appropriate cacheability metadata whenever possible.
        if (isset($context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY])) {
          $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]->addCacheableDependency($this->configFactory->get('hal.settings'));
        }
        return rtrim($domain, '/');
      }
      else {
        // Bubble the relevant cacheability metadata whenever possible.
        if (isset($context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY])) {
          $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]->addCacheContexts(['url.site']);
        }
        $request = $this->requestStack->getCurrentRequest();
        return $request->getSchemeAndHttpHost() . $request->getBasePath();
      }
    }
    return $this->linkDomain;
  }

}
