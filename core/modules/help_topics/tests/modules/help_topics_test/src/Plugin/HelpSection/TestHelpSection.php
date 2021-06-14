<?php

namespace Drupal\help_topics_test\Plugin\HelpSection;

use Drupal\help_topics\SearchableHelpInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;

/**
 * Provides a searchable help section for testing.
 *
 * @HelpSection(
 *   id = "help_topics_test",
 *   title = @Translation("Test section"),
 *   weight = 100,
 *   description = @Translation("For testing search"),
 *   permission = "access test help"
 * )
 */
class TestHelpSection extends HelpSectionPluginBase implements SearchableHelpInterface {

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    return [
      Link::fromTextAndUrl('Foo', Url::fromUri('https://foo.com')),
      Link::fromTextAndUrl('Bar', Url::fromUri('https://bar.com')),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function listSearchableTopics() {
    return ['foo', 'bar'];
  }

  /**
   * {@inheritdoc}
   */
  public function renderTopicForSearch($topic_id, LanguageInterface $language) {
    switch ($topic_id) {
      case 'foo':
        if ($language->getId() == 'en') {
          return [
            'title' => 'Foo in English title wcsrefsdf',
            'text' => 'Something about foo body notawordenglish sqruct',
            'url' => Url::fromUri('https://foo.com'),
          ];
        }
        return [
          'title' => 'Foomm Foreign heading',
          'text' => 'Fake foreign foo text notawordgerman asdrsad',
          'url' => Url::fromUri('https://mm.foo.com'),
        ];

      case 'bar':
        if ($language->getId() == 'en') {
          return [
            'title' => 'Bar in English',
            'text' => 'Something about bar anotherwordenglish asdrsad',
            'url' => Url::fromUri('https://bar.com'),
          ];
        }
        return [
          'title' => \Drupal::state()->get('help_topics_test:translated_title', 'Barmm Foreign sdeeeee'),
          'text' => 'Fake foreign barmm anotherwordgerman sqruct',
          'url' => Url::fromUri('https://mm.bar.com'),
        ];

      default:
        throw new \InvalidArgumentException('Unexpected ID encountered');
    }
  }

}
