<?

#
# Database settings:
#

### host: "http://www.drop.org/":
#$db_host = "zind.net";
#$db_uname = "droporg";
#$db_pass = "DropIes";
#$db_name = "droporg";

### host: "http://beta.drop.org/":
#$db_host = "zind.net";
#$db_uname = "dries";
#$db_pass = "Abc123";
#$db_name = "dries";

### host: "http://localhost/":
$db_host = "";
$db_uname = "dries";
$db_pass = "oakley";
$db_name = "dries";

#
# Administrative information
#
$site_name = "drop.org";
$site_url  = "http://www.drop.org/";
$site_email = "droppies@drop.org";

#
# Notify information:
#  The notify information will be used to send out automated mails
#  for internal purpose.
#
$notify_email   = $contact_email;
$notify_subject = "submission: ";
$notify_message = "New submission: '$subject'\n\n$story";
$notify_from    = "droppies@drop.org";

#
# Comment meta reasons:
#
$comment_votes = array("none" => "none", 
                       "-1"   => "- 1", 
                       "0"    => "+ 0",
                       "+1"   => "+ 1",
                       "+2"   => "+ 2",
                       "+3"   => "+ 3", 
                       "+4"   => "+ 4", 
                       "+5"   => "+ 5");

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
                    "News & Media",
                    "Politics & Freedom",
                    "Reviews",
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
#   the first theme listed in the associative array `$themes' will 
#   automatically become the default theme.
#
$themes = array("Marvin"  => array(
                   "themes/marvin/marvin.theme", 
                   "classic theme, white, basic design with a fresh look"),
                "Zaphod"  => array(
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
#   registered users can vote whether they think the story should
#   be posted or not.  When enough people vote to post a story, the 
#   story is pushed over the threshold and up it goes on the public 
#   page.  On the other hand, when too many people voted to drop a 
#   story, the story will get trashed.
$submission_post_threshold = "3";
$submission_dump_threshold = "-2";

#
# Submission rate:
#
$submission_rate = array("comment" => "60",     //  60 seconds = 1 minute
                         "diary"   => "300",    // 300 seconds = 5 minutes
                         "story"   => "300");   // 300 seconds = 5 minutes
#
# Submission size:
#  the maximum length in characters a submission (story, diary, 
#  comment) is allowed to be.
#
$submission_size = 6000;

#
# Watchdog history:
#
$watchdog_history = 604800;   // 604800 seconds = 1 week

?>
