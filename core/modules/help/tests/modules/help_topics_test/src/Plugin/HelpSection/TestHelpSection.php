<?php

namespace Drupal\help_topics_test\Plugin\HelpSection;

use Drupal\help\SearchableHelpInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;
use Drupal\help\Attribute\HelpSection;
use Drupal\Core\StringTranslation\TranslatableMarkup;

// cspell:ignore asdrsad barmm foomm sqruct wcsrefsdf sdeeeee

/**
 * Provides a searchable help section for testing.
 */
#[HelpSection(
  id: 'help_topics_test',
  title: new TranslatableMarkup('Test section'),
  description: new TranslatableMarkup('For testing search'),
  permission: 'access test help',
  weight: 100
)]
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
            'text' => 'Something about foo body not-a-word-english sqruct',
            'url' => Url::fromUri('https://foo.com'),
          ];
        }
        return [
          'title' => 'Foomm Foreign heading',
          'text' => 'Fake foreign foo text not-a-word-german asdrsad',
          'url' => Url::fromUri('https://mm.foo.com'),
        ];

      case 'bar':
        if ($language->getId() == 'en') {
          return [
            'title' => 'Bar in English',
            'text' => 'Something about bar another-word-english asdrsad',
            'url' => Url::fromUri('https://bar.com'),
          ];
        }
        return [
          'title' => \Drupal::state()->get('help_topics_test:translated_title', 'Barmm Foreign sdeeeee'),
          'text' => 'Fake foreign barmm another-word-german sqruct',
          'url' => Url::fromUri('https://mm.bar.com'),
        ];

      default:
        throw new \InvalidArgumentException('Unexpected ID encountered');
    }
  }

}
