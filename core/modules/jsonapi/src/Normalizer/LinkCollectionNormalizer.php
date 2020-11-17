<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Component\Utility\Crypt;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;

/**
 * Normalizes a LinkCollection object.
 *
 * The JSON:API specification has the concept of a "links collection". A links
 * collection is a JSON object where each member of the object is a
 * "link object". Unfortunately, this means that it is not possible to have more
 * than one link for a given key.
 *
 * When normalizing more than one link in a LinkCollection with the same key, a
 * unique and random string is appended to the link's key after a double dash
 * (--) to differentiate the links. See this class's hashByHref() method for
 * details.
 *
 * This may change with a later version of the JSON:API specification.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class LinkCollectionNormalizer extends NormalizerBase {

  /**
   * The normalizer $context key name for the key of an individual link.
   *
   * @var string
   */
  const LINK_KEY = 'jsonapi_links_object_link_key';

  /**
   * The normalizer $context key name for the context object of the link.
   *
   * @var string
   */
  const LINK_CONTEXT = 'jsonapi_links_object_context';

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = LinkCollection::class;

  /**
   * A random string to use when hashing links.
   *
   * This string is unique per instance of a link collection, but always the
   * same within it. This means that link key hashes will be non-deterministic
   * for outside observers, but two links within the same collection will always
   * have the same hash value.
   *
   * This is not used for cryptographic purposes.
   *
   * @var string
   */
  protected $hashSalt;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    assert($object instanceof LinkCollection);
    $normalized = [];
    /* @var \Drupal\jsonapi\JsonApiResource\Link $link */
    foreach ($object as $key => $links) {
      $is_multiple = count($links) > 1;
      foreach ($links as $link) {
        $link_key = $is_multiple ? sprintf('%s--%s', $key, $this->hashByHref($link)) : $key;
        $attributes = $link->getTargetAttributes();
        $normalization = array_merge(['href' => $link->getHref()], !empty($attributes) ? ['meta' => $attributes] : []);
        $normalized[$link_key] = new CacheableNormalization($link, $normalization);
      }
    }
    return CacheableNormalization::aggregate($normalized);
  }

  /**
   * Hashes a link using its href and its target attributes, if any.
   *
   * This method generates an unpredictable, but deterministic, 7 character
   * alphanumeric hash for a given link.
   *
   * The hash is unpredictable because a random hash salt will be used for every
   * request. The hash is deterministic because, within a single request, links
   * with the same href and target attributes (i.o.w. duplicates) will generate
   * equivalent hash values.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Link $link
   *   A link to be hashed.
   *
   * @return string
   *   A 7 character alphanumeric hash.
   */
  protected function hashByHref(Link $link) {
    // Generate a salt unique to each instance of this class.
    if (!$this->hashSalt) {
      $this->hashSalt = Crypt::randomBytesBase64();
    }
    // Create a dictionary of link parameters.
    $link_parameters = [
      'href' => $link->getHref(),
    ] + $link->getTargetAttributes();
    // Serialize the dictionary into a string.
    foreach ($link_parameters as $name => $value) {
      $serialized_parameters[] = sprintf('%s="%s"', $name, implode(' ', (array) $value));
    }
    // Hash the string.
    $b64_hash = Crypt::hashBase64($this->hashSalt . implode('; ', $serialized_parameters));
    // Remove any dashes and underscores from the base64 hash and then return
    // the first 7 characters.
    return substr(str_replace(['-', '_'], '', $b64_hash), 0, 7);
  }

}
