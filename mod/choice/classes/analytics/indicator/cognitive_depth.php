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
 * Cognitive depth indicator - choice.
 *
 * @package   mod_choice
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_choice\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * Cognitive depth indicator - choice.
 *
 * @package   mod_choice
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cognitive_depth extends activity_base {

    /**
     * get_name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('indicator:cognitivedepthchoice', 'mod_choice');
    }

    /**
     * get_indicator_type
     *
     * @return string
     */
    protected function get_indicator_type() {
        return self::INDICATOR_COGNITIVE;
    }

    /**
     * get_cognitive_depth_level
     *
     * @param \cm_info $cm
     * @return int
     */
    protected function get_cognitive_depth_level(\cm_info $cm) {
        $this->fill_choice_data($cm);

        if ($this->choicedata[$cm->instance]->showresults == 0 || $this->choicedata[$cm->instance]->showresults == 4) {
            // Results are not shown to students or are always shown.
            return 2;
        }

        return 3;
    }
}
