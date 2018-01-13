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
 * Bulk upload Amazon files to Vimeo by URL.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2018 Andriy Semenets (semteacher@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->libdir/adminlib.php");

//require_once("$CFG->libdir/vimeo/autoload.php");
require_once ("$CFG->dirroot/vendor/autoload.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('courseid'=>0, 'help'=>false),
                                               array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Vimeo upload settings.

Options:
--courseid=ID Course ID
-h, --help            Print out this help

Example:
\$ sudo -u www-data /usr/bin/php admin/cli/pull2vimeo.php --courseid=5
"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

if ($options['courseid']>0) {

	//init Vimeo lib
	$access_token = '4c3c32c1eea6060254cb3a1ea5daa714';
	$lib = new \Vimeo\Vimeo(null, null);
	$lib->setToken($access_token);
	
	//get list of url resoures from course
	$table = 'url';
	$urlarrayset = $DB->get_records($table,array('course'=>$options['courseid']));
	
	//process each url resource
	foreach ($urlarrayset as $urlrec){
		//submit only Amazon to Vimeo
		if (!(strpos($urlrec->externalurl, 'https://vimeo.com')===0)) {
			if (strpos($urlrec->externalurl, 'amazonaws.com')>0){
			echo "Found Amazon URL in Moodle: ".$urlrec->externalurl." \n";
			$response = $lib->request('/me/videos', array('type' => 'pull', 'link' => $urlrec->externalurl), 'POST');
			//update URL Resource data on success:
			if ($response['status']==200){
				echo "Vimeo URL: ".$response['body']['link']."\n";
				$urlrec->externalurl = $response['body']['link'];
					if ($DB->update_record($table, $urlrec, $bulk=true)) {
						echo "Moodle URL Resource record has been updated successfully! \n";
					} else {
						echo "Error writing to Moodle database \n";
					}
			} else {
				echo "Error on submit to Vimeo, possibe cause - non-video or incorrect link \n";
			}
			} else {
				echo "Nothing processed - Amazon URL not found! \n";
			}
		} else {
			echo "Nothing processed - Vimeo URL found! \n";
		}
	}
	echo "executed OK! \n";	
}

