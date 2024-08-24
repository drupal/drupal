<?php

declare(strict_types=1);

namespace Drupal\vocabulary_serialization_test;

use Drupal\taxonomy\VocabularyInterface;

class VocabularySerializationTestController {

  public function vocabularyResponse(VocabularyInterface $taxonomy_vocabulary) {
    $response = new VocabularyResponse('this is the output');
    $response->setVocabulary($taxonomy_vocabulary);
    return $response;
  }

}
