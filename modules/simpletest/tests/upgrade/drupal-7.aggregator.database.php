<?php
/**
 * @file
 * Test content for the aggregator update path.
 */

db_insert('aggregator_feed')->fields(array(
  'fid',
  'title',
  'url',
  'refresh',
  'checked',
  'queued',
  'link',
  'description',
  'image',
  'hash',
  'etag',
  'modified',
  'block',
))
  ->values(array(
  'fid' => '1',
  'title' => 'Drupal commit log',
  'url' => 'http://drupal.org/commitlog/feed',
  'refresh' => '3600',
  'checked' => '1347209523',
  'queued' => '0',
  'link' => 'http://drupal.org/versioncontrol/garbage/path',
  'description' => '',
  'image' => '',
  'hash' => '84f57ae5bffa7fd56942a6293be91244d8551cd18204a7c7de6a17065ea4d54d',
  'etag' => '"1347206975"',
  'modified' => '1347206975',
  'block' => '5',
))
  ->execute();

db_insert('aggregator_item')->fields(array(
  'iid',
  'fid',
  'title',
  'link',
  'author',
  'description',
  'timestamp',
  'guid',
))
  ->values(array(
  'iid' => '1',
  'fid' => '1',
  'title' => 'Domain Access: Commit b904022 on 7.x-2.x authored by bforchhammer, committed by agentrickard',
  'link' => 'http://drupal.org/commitlog/commit/2%2C410/b90402243b4a9dee0d2e2c4a729dcb2f58dc53c0',
  'author' => 'bforchhammer',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-10\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/domain.git/blob/b90402243b4a9dee0d2e2c4a729dcb2f58dc53c0:/domain_source/domain_source.info\">/domain_source/domain_source.info</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">1 addition & 1 deletion</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"minus\">-</span><span class=\"no-op\"> </span><span class=\"no-op\"> </span><span class=\"no-op\"> </span><span class=\"no-op\"> </span><span class=\"no-op\"> </span></span></span>\n  </div>\n  </div>\n  <div class=\"views-row views-row-2 views-row-even views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/domain.git/blob/b90402243b4a9dee0d2e2c4a729dcb2f58dc53c0:/domain_source/domain_source.views.inc\">/domain_source/domain_source.views.inc</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">13 additions & 1 deletion</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>Patch #1685658 by bforchhammer. Better handling of current domain for Domain Source.\n</pre>",
  'timestamp' => '1347206044',
  'guid' => 'VCS Operation 3936918 at http://drupal.org',
))
  ->values(array(
  'iid' => '2',
  'fid' => '1',
  'title' => 'Video: Commit b0b7ff0 on 7.x-2.x by Jorrit',
  'link' => 'http://drupal.org/commitlog/commit/846/b0b7ff08fed89c76454aa54627cc219361365d7b',
  'author' => 'Jorrit',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-9\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/video.git/blob/b0b7ff08fed89c76454aa54627cc219361365d7b:/libraries/phpvideotoolkit/phpvideotoolkit.php5.php\">/libraries/phpvideotoolkit/phpvideotoolkit.php5.php</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">5 additions & 5 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span><span class=\"minus\">-</span><span class=\"minus\">-</span><span class=\"no-op\"> </span></span></span>\n  </div>\n  </div>\n  <div class=\"views-row views-row-2 views-row-even\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/video.git/blob/b0b7ff08fed89c76454aa54627cc219361365d7b:/tests/TranscoderAbstractionFactoryFfmpeg.test\">/tests/TranscoderAbstractionFactoryFfmpeg.test</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">21 additions & 7 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span><span class=\"minus\">-</span></span></span>\n  </div>\n  </div>\n  <div class=\"views-row views-row-3 views-row-odd views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/video.git/blob/b0b7ff08fed89c76454aa54627cc219361365d7b:/transcoders/TranscoderAbstractionFactoryFfmpeg.inc\">/transcoders/TranscoderAbstractionFactoryFfmpeg.inc</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">31 additions & 22 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span><span class=\"minus\">-</span><span class=\"minus\">-</span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>Issue #1492296 by Jorrit: Added support for avconv binaries instead of FFmpeg.\n</pre>",
  'timestamp' => '1347206397',
  'guid' => 'VCS Operation 3936924 at http://drupal.org',
))
  ->values(array(
  'iid' => '3',
  'fid' => '1',
  'title' => 'Remove Login Tabs: Commit 6e1eb5a on 7.x-1.x by highrockmedia',
  'link' => 'http://drupal.org/commitlog/commit/41%2C610/6e1eb5a4a952db3264e7696e840ac3d797f4b477',
  'author' => 'highrockmedia',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-8\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/sandbox/highrockmedia/1702096.git/blob/6e1eb5a4a952db3264e7696e840ac3d797f4b477:/readme.txt\">/readme.txt</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">10 additions & 2 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>Updating readme\n</pre>",
  'timestamp' => '1347206401',
  'guid' => 'VCS Operation 3936920 at http://drupal.org',
))
  ->values(array(
  'iid' => '4',
  'fid' => '1',
  'title' => 'TimeGroup: Commit 6ed4c08 on 7.x-1.x by Sweetchuck',
  'link' => 'http://drupal.org/commitlog/commit/40%2C448/6ed4c085e5d9a8d33e091e1b8a65c73eab2dc99e',
  'author' => 'Sweetchuck',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-7\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/sandbox/Sweetchuck/1666642.git/blob/6ed4c085e5d9a8d33e091e1b8a65c73eab2dc99e:/includes/ctools/export_ui/timegroup.inc\">/includes/ctools/export_ui/timegroup.inc</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">1 addition & 1 deletion</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"minus\">-</span><span class=\"no-op\"> </span><span class=\"no-op\"> </span><span class=\"no-op\"> </span><span class=\"no-op\"> </span><span class=\"no-op\"> </span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>CTools UI - Wrong default value for timeoffset fix.\n</pre>",
  'timestamp' => '1347206533',
  'guid' => 'VCS Operation 3936942 at http://drupal.org',
))
  ->values(array(
  'iid' => '5',
  'fid' => '1',
  'title' => 'Domain Access: Commit 1140172 on 6.x-2.x authored by bforchhammer, committed by agentrickard',
  'link' => 'http://drupal.org/commitlog/commit/2%2C410/11401723f5c5d11032dd141ba4939ed889a7a915',
  'author' => 'bforchhammer',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-6\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/domain.git/blob/11401723f5c5d11032dd141ba4939ed889a7a915:/domain_source/domain_source.views.inc\">/domain_source/domain_source.views.inc</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">33 additions & 1 deletion</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"no-op\"> </span></span></span>\n  </div>\n  </div>\n  <div class=\"views-row views-row-2 views-row-even views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/domain.git/blob/11401723f5c5d11032dd141ba4939ed889a7a915:/domain_source/includes/domain_source_handler_filter_domain_id.inc\">/domain_source/includes/domain_source_handler_filter_domain_id.inc</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">28 additions & 0 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>Patch #1685658 by bforchhammer. Better handling of current domain for Domain Source.\n</pre>",
  'timestamp' => '1347206541',
  'guid' => 'VCS Operation 3936926 at http://drupal.org',
))
  ->values(array(
  'iid' => '6',
  'fid' => '1',
  'title' => 'Domain Access: Commit 19b1c36 on 7.x-2.x by agentrickard',
  'link' => 'http://drupal.org/commitlog/commit/2%2C410/19b1c366d86cecd8a9f6e1a6e835c0566f5c02db',
  'author' => 'agentrickard',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-5\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/domain.git/blob/19b1c366d86cecd8a9f6e1a6e835c0566f5c02db:/domain_source/includes/domain_source_handler_filter_domain_id.inc\">/domain_source/includes/domain_source_handler_filter_domain_id.inc</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">28 additions & 0 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>Adds new Views file to Domain Source.\n</pre>",
  'timestamp' => '1347206601',
  'guid' => 'VCS Operation 3936928 at http://drupal.org',
))
  ->values(array(
  'iid' => '7',
  'fid' => '1',
  'title' => 'Domain Access: Commit d2d5456 on 7.x-3.x by agentrickard',
  'link' => 'http://drupal.org/commitlog/commit/2%2C410/d2d5456cad6ca57bb72e743da6a7112a74d7a331',
  'author' => 'agentrickard',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-4\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/domain.git/blob/d2d5456cad6ca57bb72e743da6a7112a74d7a331:/domain_source/includes/domain_source_handler_filter_domain_id.inc\">/domain_source/includes/domain_source_handler_filter_domain_id.inc</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">29 additions & 0 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>Adds new Views file to Domain Source.\n</pre>",
  'timestamp' => '1347206620',
  'guid' => 'VCS Operation 3936930 at http://drupal.org',
))
  ->values(array(
  'iid' => '8',
  'fid' => '1',
  'title' => 'Skarabee: Commit 400b519 on 7.x-1.x by sboersma',
  'link' => 'http://drupal.org/commitlog/commit/23%2C278/400b5190f59b1cb58d6b27fa10ac668e9580aa73',
  'author' => 'sboersma',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-3\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/sandbox/sboersma/1176520.git/blob/400b5190f59b1cb58d6b27fa10ac668e9580aa73:/skarabee.install\">/skarabee.install</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">3 additions & 3 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span><span class=\"minus\">-</span><span class=\"minus\">-</span><span class=\"no-op\"> </span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>sboersma: Changed variable deletion method.\n</pre>",
  'timestamp' => '1347206709',
  'guid' => 'VCS Operation 3936932 at http://drupal.org',
))
  ->values(array(
  'iid' => '9',
  'fid' => '1',
  'title' => 'Config entity listing plugin API: Commit dd3fa73 on 8.x-list by damiankloip',
  'link' => 'http://drupal.org/commitlog/commit/43%2C586/dd3fa73b0bcdca833bbde1d1ddb3cefe42003693',
  'author' => 'damiankloip',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-2\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/sandbox/damiankloip/1778654.git/blob/dd3fa73b0bcdca833bbde1d1ddb3cefe42003693:/core/modules/config/lib/Drupal/config/Tests/ConfigEntityListingTest.php\">/core/modules/config/lib/Drupal/config/Tests/ConfigEntityListingTest.php</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">10 additions & 2 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>Added tests for getList() method\n</pre>",
  'timestamp' => '1347206738',
  'guid' => 'VCS Operation 3936936 at http://drupal.org',
))
  ->values(array(
  'iid' => '10',
  'fid' => '1',
  'title' => 'AutoSlave: Commit 76891da on 7.x-1.x by gielfeldt',
  'link' => 'http://drupal.org/commitlog/commit/42%2C968/76891daf3cea9c294daf56a26760cb1bf33ea58a',
  'author' => 'gielfeldt',
  'description' => "<div class=\"view view-commitlog-commit-items view-id-commitlog_commit_items view-display-id-block_1 view-dom-id-1\">\n    \n  \n  \n      <div class=\"view-content\">\n        <div class=\"views-row views-row-1 views-row-odd views-row-first\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/autoslave.git/blob/76891daf3cea9c294daf56a26760cb1bf33ea58a:/autoslave.module\">/autoslave.module</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">10 additions & 7 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span><span class=\"minus\">-</span><span class=\"minus\">-</span></span></span>\n  </div>\n  </div>\n  <div class=\"views-row views-row-2 views-row-even views-row-last\">\n      \n  <div class=\"views-field-path\">\n                <span class=\"field-content\"><a href=\"http://drupalcode.org/project/autoslave.git/blob/76891daf3cea9c294daf56a26760cb1bf33ea58a:/autoslave/database.inc\">/autoslave/database.inc</a></span>\n  </div>\n  \n  <div class=\"views-field-changed-lines\">\n                <span class=\"field-content\">10 additions & 2 deletions</span>\n  </div>\n  \n  <div class=\"views-field-visual-diffstat\">\n                <span class=\"field-content\"><span class=\"versioncontrol-diffstat clear-block\"><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"plus\">+</span><span class=\"minus\">-</span></span></span>\n  </div>\n  </div>\n    </div>\n  \n  \n  \n  \n  \n  \n</div>\n<pre>Keep track of affected tables per commit.\n</pre>",
  'timestamp' => '1347206751',
  'guid' => 'VCS Operation 3936934 at http://drupal.org',
))
  ->execute();
