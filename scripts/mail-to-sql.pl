#!/usr/bin/perl -w

use DBI;

# database settings:
my $db_name = 'drop';
my $db_user = 'drop';
my $db_pass = 'drop';

# read data from stdin:
my @data = <STDIN>;
my $data = join '', @data;

my @chunks = split(/\n\n/, $data);

# parse the header into an associative array:
foreach $line (split(/\n/, $chunks[0])) {
  if ($line =~ /(.*?):\s(.*)/) {
    $header{lc($1)} = $2;
  }
  $header{data} .= "$line\n";
}

$chunks[0] = "";

# debug output:
 # foreach $key (sort keys %header) {
 #   print "$key: $header{$key}\n";
 # }

# construct the mail body:
foreach $line (@chunks) {
  $body .= "$line\n\n";
}

my $db = DBI->connect("DBI:mysql:$db_name", "$db_user", "$db_pass") or die "Couldn't connect recepient database: " . DBI->errstr;
$db->do("INSERT INTO mail (subject, sender, recepient, header, body, timestamp) VALUES (". $db->quote($header{subject}) .", ". $db->quote($header{from}) .", ". $db->quote($header{to}) .", ". $db->quote($header{data}) .", ". $db->quote($body) .", ". $db->quote(time()) .")") or die "Couldn't execute query: " . $db->errstr;
$db->disconnect();
