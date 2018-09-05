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

list($options, $unrecognized) = cli_get_params(
		array('help'=>false),
		array('h'=>'help')
		);
if($unrecognized) {
	$unrecognized = implode("\n  ", $unrecognized);
	cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
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


cli_heading('Facebook notifications');

mtrace("Searching for new notifications");
mtrace("Starting at ".date("F j, Y, G:i:s"));

$initialtime = time();
$notifications = 0;

$appid = $CFG->fbk_appid;
$secretid = $CFG->fbk_scrid;

$fb = facebook_newclass();

$queryusers = "SELECT
		us.id AS id,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {facebook_user} AS fb
		RIGHT JOIN {user} AS us ON (us.id = fb.moodleid AND fb.status = ?)
		WHERE fb.facebookid IS NOT NULL
		GROUP BY fb.facebookid, us.id";

$queryassignments = "SELECT CONCAT(us.id,'.',a.id) AS userassign,
		us.id AS userid,
		a.id AS assignid,
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

$querysubmissions = "SELECT CONCAT(us.id,'.',a.id) AS userassign,
		us.id AS userid,
		a.id AS assignid,
		c.id AS courseid,
		c.fullname AS coursename,
		fb.facebookid,
		CONCAT(us.firstname,' ',us.lastname) AS name
		FROM {assign_submission} as asub
		INNER JOIN {assign} AS a ON (asub.assignment = a.id)
		INNER JOIN {course} AS c ON (a.course = c.id)
		INNER JOIN {enrol} AS e ON (c.id = e.courseid)
		INNER JOIN {user_enrolments} AS ue ON (e.id = ue.enrolid)
		INNER JOIN {user} AS us ON (us.id = ue.userid)

		INNER JOIN {facebook_user} AS fb ON (fb.moodleid = us.id AND fb.status = ?)
		WHERE a.duedate > ?
		AND fb.facebookid IS NOT NULL
		AND status = ?
		
		GROUP BY us.id, a.id, c.id
		ORDER BY us.id";

$paramsusers = array(
		FACEBOOK_LINKED
);
$paramsassignment = array(
		MODULE_ASSIGN,
		FACEBOOK_COURSE_MODULE_VISIBLE,
		$initialtime
);
$paramsubmission = array(
		MODULE_ASSIGN,
		FACEBOOK_COURSE_MODULE_VISIBLE,
		$initialtime,
		"submitted"
);

$arraynewassignments = array();
$arraysubmissions = array();
$arraynewassignments = facebook_addtoarray($queryassignments, array_merge($paramsassignment, $paramsusers), $arraynewassignments);
$arraysubmissions = facebook_addtoarray($querysubmissions, array_merge($paramsubmission, $paramsusers), $arraysubmissions);

$notsubmitted = array();
foreach($arraynewassignments as $assignments){
	if (!in_array($assignments->userassign, $arraysubmissions)){
		$notsubmitted[] = $assignments;
	}
}

$countnotsubmittedusers = array();
foreach($notsubmitted as $user){
	if (isset($countnotsubmittedusers[$user->userid])){
		$countnotsubmittedusers[$user->userid] = $countnotsubmittedusers[$user->userid] + 1;
	}
	else{
		$countnotsubmittedusers[$user->userid] = 1;
	}
}

if ($facebookusers = $DB->get_records_sql($queryusers, $paramsusers)){
	foreach ($facebookusers as $users){
		if (isset($countnotsubmittedusers[$users->id])){
			if ($countnotsubmittedusers[$users->id] == 1){
				$template = "Usted tiene una tarea pendiente en el ramo de -falta terminar la query-";
			}
			elseif($countnotsubmittedusers[$users->id] > 1){
				$template = "Usted tiene " + $countnotsubmittedusers[$users->id] + " tareas pendientes que entregar";
			}
		}
		
		if ($users->facebookid != null && $countnotsubmittedusers[$users->id] > 0) {
			$data = array(
					"link" => "",
					"message" => "",
					"template" => $template
			);
			$fb->setDefaultAccessToken($appid.'|'.$secretid);
			if (facebook_handleexceptions($fb, $users, $data)){
				mtrace(" Notifications sent to user with moodleid ".$users->id." - ".$users->name);
				$notifications = $notifications + 1;
			}
		}
	}
	mtrace("Notifications have been sent succesfully to ".$notifications." people.");
	$finaltime = time();
	$totaltime = $finaltime-$initialtime;
	mtrace("Execution time: ".$totaltime." seconds.");
}
exit(0);
