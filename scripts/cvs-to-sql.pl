#!/usr/bin/perl -w

use DBI;

my $db_name = 'drupal';
my $db_user = 'drupal';
my $db_pass = 'drupal';

my $files = $ARGV[0];
my @message = <STDIN>;
my $message = join '' , @message;
my $user = $ENV{USER};
my $timestamp = time();

my $db = DBI->connect("DBI:mysql:$db_name", "$db_user", "$db_pass") or die "Couldn't connect to database: " . DBI->errstr;
$db->do("INSERT INTO cvs (user, files, message, timestamp) VALUES (". $db->quote($user) .", ". $db->quote($files) .", ". $db->quote($message) .", ". $db->quote($timestamp) .")") or die "Couldn't execute query: " . $db->errstr;
$db->disconnect();
