<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script send notifications on facebook
*
* @package    local/facebook/
* @subpackage cli
* @copyright  2010 Jorge Villalon (http://villalon.cl)
* @copyright  2015 Mihail Pozarski (mipozarski@alumnos.uai.cl)
* @copyright  2015 - 2016 Hans Jeria (hansjeria@gmail.com)
* @copyright  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
* @copyright  2018 Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

//define('CLI_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once ($CFG->libdir . '/clilib.php');
require_once ($CFG->dirroot."/local/facebook/locallib.php");
//require_once($CFG->dirroot."/local/facebook/app/Facebook/autoload.php");
//require_once($CFG->dirroot."/local/facebook/app/Facebook/FacebookRequest.php");
//include $CFG->dirroot."/local/facebook/app/Facebook/Facebook.php";
use Facebook\FacebookResponse;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequire;
use Facebook\Facebook;
use Facebook\Request;

/*
 if($options['help']) {
 $help =
 "Send facebook notifications when a course have some news.
 Options:
 -h, --help            Print out this help
 Example:
 \$sudo /usr/bin/php /local/facebook/cli/notifications.php";
 echo $help;
 die();
 }
 */


mtrace("Searching for new notifications");
mtrace("Starting at ".date("F j, Y, G:i:s"));
echo '\n';
$initialtime = time();
$notifications = 0;

$appid = $CFG->fbk_appid;
$secretid = $CFG->fbk_scrid;

$fb = facebook_newclass();

$newquery = "SELECT CONCAT(us.id,'.',a.id) AS userassign,
		us.id AS id,
		a.id AS assignid,
		a.duedate AS duedate,
		c.id AS courseid,
		c.fullname AS coursename,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {assign} AS a
		INNER JOIN {course} AS c ON (a.course = c.id)
		INNER JOIN {enrol} AS e ON (c.id = e.courseid)
		INNER JOIN {user_enrolments} AS ue ON (e.id = ue.enrolid)
		INNER JOIN {user} AS us ON (us.id = ue.userid)

		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE a.duedate > ?
		AND fb.facebookid IS NOT NULL
		
		GROUP BY us.id, a.id, c.id
		ORDER BY us.id";

$myid = $DB->get_records_sql($newquery, array(FACEBOOK_LINKED, $initialtime));

var_dump($myid);

$data = array(
		"link" => "lala",
		"message" => "Le test",
		"template" => "This is a test"
);

foreach ($myid as $users){
$fb->setDefaultAccessToken($appid.'|'.$secretid);
if ($lala = facebook_handleexceptions($fb, $users, $data)){
	echo '\n';
	var_dump($lala);
	mtrace(" Notifications sent to user with facebook ".$users->facebookid." - ".$users->name."\n");
}}