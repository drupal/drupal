<?

#
# MySQL settings:
#

$dbhost = "zind.net";
$dbuname = "dries";
$dbpass = "Abc123";
$dbname = "dries";

#$dbhost = "";
#$dbuname = "dries";
#$dbpass = "oakley";
#$dbname = "dries";

#
# Name of the site
#
$sitename = "drop.org";

#
# Contact information:
#  The contact information will be used to send out automated mails
#  to users, account holders or visitors.
$contact_email = "droppies@zind.net";

#
# Notify:
#   Set to '1' to receive an e-mail when news has been submitted
#   through submit.php
#
$notify = 0;

#
# Notify information:
#  The notify information will be used to send out automated mails
#  for internal purpose.
#
$notify_email   = $contact_email;
$notify_subject = "submission: ";
$notify_message = "New submission: '$subject'\n\n$story";
$notify_from    = "droppies@zind.net";

#
# Comment meta reasons:
#
$comment_votes = array("none" => "none", "-1" => "- 1", "0" => "+ 0", "+1" => "+ 1", "+2" => "+ 2", "+3" => "+ 3", "+4" => "+ 4", "+5" => "+ 5");

#
# Categories:
#
$categories = array("Announcements",
                    "Arts & Humanities",
                    "Business & Economy",
                    "Coding & Webdesign",
                    "Computers & Internet",
                    "Drop.org",
                    "Entertainment",
                    "Freedom",
                    "Government",
                    "News & Media",
                    "Science", 
                    "Society & Culture");

# 
# Allowed HTML tags:
#
$allowed_html = "<A><B><BR><DD><DL><DT><EM><HR><I><IL><SMALL><OL><U><UL>";

#
# Name of the 'anonymous' user account:
#
$anonymous = "Anonymous Chicken";


#
# Themes:
#
$themes = array("Marvin" => array(
                   "themes/marvin/marvin.theme", 
                   "classic theme, white, basic design with a fresh look"),
                "Zaphod" => array(
                   "themes/zaphod/zaphod.theme", 
                   "classis theme, yellow, structured, advanced navigation"),
		"UnConeD" => array(
		   "themes/unconed/unconed.theme",
		   "modern theme, gray and blue, high coolness factor"));

#
# Submission moderation votes:
#   changing $submission_votes will affect the integrity of your 
#   database.  In short, the database field user.history will 
#   become invalid, and will need to be reset! Please, do not 
#   change this setting unless you have an empty database or 
#   unless you know what you are doing.
$submission_votes = array("neutral (+0)" => "+ 0",
                          "post it (+1)" => "+ 1",
                          "dump it (-1)" => "- 1");

#
# Submission moderation thresholds:
#
$submission_post_threshold = "2";
$submission_dump_threshold = "-2";

?>