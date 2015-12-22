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
 * 
 *
 * @package    local
 * @subpackage facebook
 * @copyright  2013 Francisco García Ralph (francisco.garcia.ralph@gmail.com)
 * @copyright  2015 Xiu-Fong Lin (xlin@alumnos.uai.cl)
 * @copyright  2015 Mihail Pozarski (mipozarski@alumnos.uai.cl)
 * @copyright  2015 Hans Jeria (hansjeria@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/local/facebook/locallib.php');
require_once ($CFG->dirroot . "/local/facebook/app/Facebook/autoload.php");
global $DB, $USER, $CFG;
include "config.php";
use Facebook\FacebookResponse;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequire;
include "htmltoinclude/javascriptindex.html";


//gets all facebook information needed
$appid = $CFG->fbkAppID;
$secretid = $CFG->fbkScrID;
$config = array(
		"app_id" => $appid,
		"app_secret" => $secretid,
		"default_graph_version" => "v2.5"
);
$fb = new Facebook\Facebook($config);

$helper = $fb->getCanvasHelper();

try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

if (! isset($accessToken)) {
  echo 'No OAuth data could be obtained from the signed request. User has not authorized your app yet.';
  exit;
}

// Logged in
echo '<h3>Signed Request</h3>';
var_dump($helper->getSignedRequest());

echo '<h3>Access Token</h3>';
var_dump($accessToken->getValue());
/*

$helper = $fb->getRedirectLoginHelper();
$permissions = ["email",
			"publish_actions",
			"user_birthday",
			"user_tagged_places",
			"user_work_history",
			"user_about_me",
			"user_hometown",
			"user_actions.books",
			"user_education_history",
			"user_likes",
			"user_friends",
			"user_religion_politics"
	];

$loginUrl = $helper->getLoginUrl(($CFG->wwwroot."/local/facebook/app/appwebcursos.php"), $permissions );

echo "<br><center><a href='" . htmlspecialchars($loginUrl) . "'><img src='app/images/login.jpg'width='180' height='30'></a><center>";

*/