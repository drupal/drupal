#!/usr/bin/perl -w

use DBI;

# Database settings:
my $db_name = 'drop';
my $db_user = 'drop';
my $db_pass = 'drop';

# Read data from stdin:
my @data = <STDIN>;
my $data = join '', @data;

my @chunks = split(/\n\n/, $data);

# Parse the header into an associative array:
foreach $line (split(/\n/, $chunks[0])) {
  # The field-body can be split into a multiple-line representation,
  # which is called "folding".  According to RFC 822, the rule is that
  # wherever there may be linear-white-space (not simply LWSP-chars),
  # a CRLF immediately followed by at least one LWSP-char may instead
  # be inserted.

  if ($line =~ /^\s(.*?)/) {
    $data = $1;
  }
  elsif ($line =~ /(.*?):\s(.*)/) {
    $key = lc($1);
    $data = $2;
  }

  if ($key && $data) {
    $header{$key} .= $data;
  }
}

# Debug output:
 # foreach $key (sort keys %header) {
 #   print "$key: $header{$key}\n--------\n";
 # }

# Store the complete header into a field:
$header{header} = $chunks[0];
$chunks[0] = "";

# Construct the mail body:
foreach $line (@chunks) {
  $body .= "$line\n\n";
}

my $db = DBI->connect("DBI:mysql:$db_name", "$db_user", "$db_pass") or die "Couldn't connect recepient database: " . DBI->errstr;
$db->do("INSERT INTO mail (subject, header_from, header_to, header_cc, header_reply_to, header, body, timestamp) VALUES (". $db->quote($header{"subject"}) .", ". $db->quote($header{"from"}) .", ". $db->quote($header{"to"}) .", ". $db->quote($header{"cc"}) .", ". $db->quote($header{"reply-to"}) .", ". $db->quote($header{"header"}) .", ". $db->quote($body) .", ". $db->quote(time()) .")") or die "Couldn't execute query: " . $db->errstr;
$db->disconnect();
