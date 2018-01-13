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
 * Enable or disable maintenance mode.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->libdir/adminlib.php");

//require("/vimeo/autoload.php");
require_once("$CFG->libdir/classes/task/adhoc_task.php");
require_once("$CFG->libdir/classes/task/manager.php");
require_once("$CFG->libdir/vimeo/autoload.php");

class pull_to_vimeo extends \core\task\adhoc_task {                                                                           
    public function execute() {       
        // gain 100,000,000 friends on facebook.
        // crash the stock market.
        // run for president.
		$lib = new \Vimeo\Vimeo(null, null);
		
		echo "executed OK!";
    }                                                                                                                               
}

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
   // create the instance
   $vimeopull = new pull_to_vimeo();
   // set blocking if required (it probably isn't)
   // $domination->set_blocking(true);
   // add custom data
   $vimeopull->set_custom_data(array(
       'courseid' => $options['courseid']
   ));

   // queue it
   \core\task\manager::queue_adhoc_task($vimeopull);
   echo "queue has started for courseID = ".$options['courseid'];
}

