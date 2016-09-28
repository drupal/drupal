<?php

namespace Drupal\vocabulary_serialization_test;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\taxonomy\VocabularyInterface;

class VocabularyResponse extends CacheableResponse {

  /**
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  public function setVocabulary(VocabularyInterface $vocabulary) {
    $this->vocabulary = $vocabulary;
  }

}
