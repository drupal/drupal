<?php

include "includes/common.inc";

db_query("DELETE FROM watchdog");
db_query("DELETE FROM nodes");
db_query("DELETE FROM story");
db_query("DELETE FROM book");

db_query("UPDATE comments SET type = 'story' WHERE type = 'stories'");

$result = db_query("SELECT * FROM stories");
while ($story = db_fetch_object($result)) {
  $node = array(title => $story->subject, abstract => $story->abstract, body => $story->article, section => $story->section, timestamp => $story->timestamp, votes => $story->votes, score => $stort->score, author => $story->author, type => "story");
  if ($story->status == 2) $node[status] = $status[posted];
  if ($story->status == 1) $node[status] = $status[queued];
  if ($story->status == 0) $node[status] = $status[dumped];
  node_save($node);
}

$result = db_query("SELECT * FROM faqs");
while ($faq = db_fetch_object($result)) {
  $node = array(title => $faq->question, author => 1, body => $faq->answer, weight => $faq->weight, status => $status[posted], type => "book");
  node_save($node);
}

db_query("update users set history = ''");

?>