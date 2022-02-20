<?php

namespace Drupal\Tests\hal\Functional\taxonomy;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\taxonomy\Functional\Rest\TermResourceTestBase;

/**
 * @group hal
 */
class TermHalJsonAnonTest extends TermResourceTestBase {

  use HalEntityNormalizationTrait;
  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $default_normalization = parent::getExpectedNormalizedEntity();

    $normalization = $this->applyHalFieldNormalization($default_normalization);

    // We test with multiple parent terms, and combinations thereof.
    // @see ::createEntity()
    // @see ::testGet()
    // @see ::testGetTermWithParent()
    // @see ::providerTestGetTermWithParent()
    // @see ::testGetTermWithParent()
    $parent_term_ids = [];
    for ($i = 0; $i < $this->entity->get('parent')->count(); $i++) {
      $parent_term_ids[$i] = (int) $this->entity->get('parent')[$i]->target_id;
    }

    $expected_parent_normalization_links = FALSE;
    $expected_parent_normalization_embedded = FALSE;
    switch ($parent_term_ids) {
      case [0]:
        $expected_parent_normalization_links = [
          NULL,
        ];
        $expected_parent_normalization_embedded = [
          NULL,
        ];
        break;

      case [2]:
        $expected_parent_normalization_links = [
          [
          'href' => $this->baseUrl . '/taxonomy/term/2?_format=hal_json',
          ],
        ];
        $expected_parent_normalization_embedded = [
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/taxonomy/term/2?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/taxonomy_term/camelids',
              ],
            ],
            'uuid' => [
              ['value' => Term::load(2)->uuid()],
            ],
          ],
        ];
        break;

      case [0, 2]:
        $expected_parent_normalization_links = [
          NULL,
          [
            'href' => $this->baseUrl . '/taxonomy/term/2?_format=hal_json',
          ],
        ];
        $expected_parent_normalization_embedded = [
          NULL,
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/taxonomy/term/2?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/taxonomy_term/camelids',
              ],
            ],
            'uuid' => [
              ['value' => Term::load(2)->uuid()],
            ],
          ],
        ];
        break;

      case [3, 2]:
        $expected_parent_normalization_links = [
          [
            'href' => $this->baseUrl . '/taxonomy/term/3?_format=hal_json',
          ],
          [
            'href' => $this->baseUrl . '/taxonomy/term/2?_format=hal_json',
          ],
        ];
        $expected_parent_normalization_embedded = [
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/taxonomy/term/3?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/taxonomy_term/camelids',
              ],
            ],
            'uuid' => [
              ['value' => Term::load(3)->uuid()],
            ],
          ],
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/taxonomy/term/2?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/taxonomy_term/camelids',
              ],
            ],
            'uuid' => [
              ['value' => Term::load(2)->uuid()],
            ],
          ],
        ];
        break;
    }

    return $normalization + [
      '_links' => [
        'self' => [
          'href' => $this->baseUrl . '/llama?_format=hal_json',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/taxonomy_term/camelids',
        ],
        $this->baseUrl . '/rest/relation/taxonomy_term/camelids/parent' => $expected_parent_normalization_links,
      ],
      '_embedded' => [
        $this->baseUrl . '/rest/relation/taxonomy_term/camelids/parent' => $expected_parent_normalization_embedded,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return parent::getNormalizedPostEntity() + [
      '_links' => [
        'type' => [
          'href' => $this->baseUrl . '/rest/type/taxonomy_term/camelids',
        ],
      ],
    ];
  }

}
