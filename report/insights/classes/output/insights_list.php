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
 * Insights list page.
 *
 * @package    report_insights
 * @copyright  2017 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_insights\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Shows report_insights insights list.
 *
 * @package    report_insights
 * @copyright  2017 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class insights_list implements \renderable, \templatable {

    /**
     * @var \core_analytics\model
     */
    protected $model;

    /**
     * @var \context
     */
    protected $context;

    /**
     * @var \core_analytics\model[]
     */
    protected $othermodels;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $perpage;

    /**
     * Constructor
     *
     * @param \core_analytics\model $model
     * @param \context $context
     * @param \core_analytics\model[] $othermodels
     * @param int $page
     * @param int $perpage The max number of results to fetch
     * @return void
     */
    public function __construct(\core_analytics\model $model, \context $context, $othermodels, $page = 0, $perpage = 100) {
        $this->model = $model;
        $this->context = $context;
        $this->othermodels = $othermodels;
        $this->page = $page;
        $this->perpage = $perpage;
    }

    /**
     * Exports the data.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE;

        $data = new \stdClass();
        $total = 0;

        if ($this->model->uses_insights()) {
            list($total, $predictions) = $this->model->get_predictions($this->context, $this->page, $this->perpage);

            $data->insights = array();
            foreach ($predictions as $prediction) {
                $insightrenderable = new \report_insights\output\insight($prediction, $this->model, true);
                $data->insights[] = $insightrenderable->export_for_template($output);
            }

            if (empty($data->insights) && $this->page == 0) {
                if ($this->model->any_prediction_obtained()) {
                    $data->noinsights = get_string('noinsights', 'analytics');
                } else {
                    $data->noinsights = get_string('nopredictionsyet', 'analytics');
                }
            }
        } else {
            $data->noinsights = get_string('noinsights', 'analytics');
        }

        if (!empty($data->noinsights)) {
            $notification = new \core\output\notification($data->noinsights);
            $data->noinsights = $notification->export_for_template($output);
        }

        if ($this->othermodels) {

            $options = array();
            foreach ($this->othermodels as $model) {
                $options[$model->get_id()] = $model->get_target()->get_name();
            }

            // New moodle_url instance returned by magic_get_url.
            $url = $PAGE->url;
            $url->remove_params('modelid');
            $modelselector = new \single_select($url, 'modelid', $options, '',
                array('' => get_string('selectotherinsights', 'report_insights')));
            $data->modelselector = $modelselector->export_for_template($output);
        }

        $data->pagingbar = $output->render(new \paging_bar($total, $this->page, $this->perpage, $PAGE->url));

        return $data;
    }
}
