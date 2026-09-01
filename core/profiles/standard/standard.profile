<?php

/**
 * @file
 * Hooks for the Standard installation profile.
 */

declare(strict_types=1);

/**
 * Implements hook_install_tasks_alter().
 */
function standard_install_tasks_alter(array &$tasks, array $install_state): void {
  $tasks['standard_install_finished'] = [];
}

/**
 * Sets a short message to welcome the user to Drupal.
 */
function standard_install_finished(array &$install_state): void {
  $args = [
    ':community' => 'https://www.drupal.org/community',
    ':values' => 'https://www.drupal.org/about/values-and-principles',
    ':user_guide' => 'https://www.drupal.org/docs/user_guide/en/index.html',
    ':extend' => 'https://www.drupal.org/docs/extending-drupal',
    ':events' => 'https://www.drupal.org/community/events',
    ':slack' => 'https://www.drupal.org/slack',
    ':support' => 'https://www.drupal.org/support',
  ];
  $message = t('<h2>Welcome to Drupal!</h2>
<p>Drupal is an open source platform made, used, taught, documented, and marketed by <a href=":community">a worldwide community</a> of people with <a href=":values">shared values</a>, creating amazing digital experiences together.</p>
<ol>
<li><a href=":user_guide">Read the User Guide</a> to learn the basics.</li>
<li><a href=":extend">Extend Drupal</a> with community contributions.</li>
</ol>
<p>Let\'s meet! Check out <a href=":events">upcoming events</a>, <a href=":slack">chat on Slack</a>, or <a href=":support">get support</a>.</p>', $args);
  \Drupal::messenger()->addStatus($message);
}
