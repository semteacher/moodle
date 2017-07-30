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
 * Analysers base class.
 *
 * @package   core_analytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics\local\analyser;

defined('MOODLE_INTERNAL') || die();

/**
 * Analysers base class.
 *
 * @package   core_analytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {

    /**
     * @var int
     */
    protected $modelid;

    /**
     * The model target.
     *
     * @var \core_analytics\local\target\base
     */
    protected $target;

    /**
     * The model indicators.
     *
     * @var \core_analytics\local\indicator\base[]
     */
    protected $indicators;

    /**
     * Time splitting methods to use.
     *
     * Multiple time splitting methods during evaluation and 1 single
     * time splitting method once the model is enabled.
     *
     * @var \core_analytics\local\time_splitting\base[]
     */
    protected $timesplittings;

    /**
     * Execution options.
     *
     * @var array
     */
    protected $options;

    /**
     * Simple log array.
     *
     * @var string[]
     */
    protected $log;

    /**
     * Constructor method.
     *
     * @param int $modelid
     * @param \core_analytics\local\target\base $target
     * @param \core_analytics\local\indicator\base[] $indicators
     * @param \core_analytics\local\time_splitting\base[] $timesplittings
     * @param array $options
     * @return void
     */
    public function __construct($modelid, \core_analytics\local\target\base $target, $indicators, $timesplittings, $options) {
        $this->modelid = $modelid;
        $this->target = $target;
        $this->indicators = $indicators;
        $this->timesplittings = $timesplittings;

        if (empty($options['evaluation'])) {
            $options['evaluation'] = false;
        }
        $this->options = $options;

        // Checks if the analyser satisfies the indicators requirements.
        $this->check_indicators_requirements();

        $this->log = array();
    }

    /**
     * This function returns this analysable list of samples.
     *
     * @param \core_analytics\analysable $analysable
     * @return array array[0] = int[] (sampleids) and array[1] = array (samplesdata)
     */
    abstract protected function get_all_samples(\core_analytics\analysable $analysable);

    /**
     * This function returns the samples data from a list of sample ids.
     *
     * @param int[] $sampleids
     * @return array array[0] = int[] (sampleids) and array[1] = array (samplesdata)
     */
    abstract public function get_samples($sampleids);

    /**
     * Returns the analysable of a sample.
     *
     * @param int $sampleid
     * @return \core_analytics\analysable
     */
    abstract public function get_sample_analysable($sampleid);

    /**
     * Returns the sample's origin in moodle database.
     *
     * @return string
     */
    abstract protected function get_samples_origin();

    /**
     * Returns the context of a sample.
     *
     * moodle/analytics:listinsights will be required at this level to access the sample predictions.
     *
     * @param int $sampleid
     * @return \context
     */
    abstract public function sample_access_context($sampleid);

    /**
     * Describes a sample with a description summary and a \renderable (an image for example)
     *
     * @param int $sampleid
     * @param int $contextid
     * @param array $sampledata
     * @return array array(string, \renderable)
     */
    abstract public function sample_description($sampleid, $contextid, $sampledata);

    /**
     * Main analyser method which processes the site analysables.
     *
     * \core_analytics\local\analyser\by_course and \core_analytics\local\analyser\sitewide are implementing
     * this method returning site courses (by_course) and the whole system (sitewide) as analysables.
     * In most of the cases you should have enough extending from one of these classes so you don't need
     * to reimplement this method.
     *
     * @param bool $includetarget
     * @return \stored_file[]
     */
    abstract public function get_analysable_data($includetarget);

    /**
     * Samples data this analyser provides.
     *
     * @return string[]
     */
    protected function provided_sample_data() {
        return array($this->get_samples_origin());
    }

    /**
     * Returns labelled data (training and evaluation).
     *
     * @return array
     */
    public function get_labelled_data() {
        return $this->get_analysable_data(true);
    }

    /**
     * Returns unlabelled data (prediction).
     *
     * @return array
     */
    public function get_unlabelled_data() {
        return $this->get_analysable_data(false);
    }

    /**
     * Checks if the analyser satisfies all the model indicators requirements.
     *
     * @throws \core_analytics\requirements_exception
     * @return void
     */
    protected function check_indicators_requirements() {

        foreach ($this->indicators as $indicator) {
            $missingrequired = $this->check_indicator_requirements($indicator);
            if ($missingrequired !== true) {
                throw new \core_analytics\requirements_exception(get_class($indicator) . ' indicator requires ' .
                    json_encode($missingrequired) . ' sample data which is not provided by ' . get_class($this));
            }
        }
    }

    /**
     * Checks that this analyser satisfies the provided indicator requirements.
     *
     * @param \core_analytics\local\indicator\base $indicator
     * @return true|string[] True if all good, missing requirements list otherwise
     */
    public function check_indicator_requirements(\core_analytics\local\indicator\base $indicator) {

        $providedsampledata = $this->provided_sample_data();

        $requiredsampledata = $indicator::required_sample_data();
        if (empty($requiredsampledata)) {
            // The indicator does not need any sample data.
            return true;
        }
        $missingrequired = array_diff($requiredsampledata, $providedsampledata);

        if (empty($missingrequired)) {
            return true;
        }

        return $missingrequired;
    }

    /**
     * Processes an analysable
     *
     * This method returns the general analysable status, an array of files by time splitting method and
     * an error message if there is any problem.
     *
     * @param \core_analytics\analysable $analysable
     * @param bool $includetarget
     * @return \stored_file[] Files by time splitting method
     */
    public function process_analysable($analysable, $includetarget) {

        // Default returns.
        $files = array();
        $message = null;

        // Target instances scope is per-analysable (it can't be lower as calculations run once per
        // analysable, not time splitting method nor time range).
        $target = call_user_func(array($this->target, 'instance'));

        // We need to check that the analysable is valid for the target even if we don't include targets
        // as we still need to discard invalid analysables for the target.
        $result = $target->is_valid_analysable($analysable, $includetarget);
        if ($result !== true) {
            $a = new \stdClass();
            $a->analysableid = $analysable->get_id();
            $a->result = $result;
            $this->add_log(get_string('analysablenotvalidfortarget', 'analytics', $a));
            return array();
        }

        // Process all provided time splitting methods.
        $results = array();
        foreach ($this->timesplittings as $timesplitting) {

            // For evaluation purposes we don't need to be that strict about how updated the data is,
            // if this analyser was analysed less that 1 week ago we skip generating a new one. This
            // helps scale the evaluation process as sites with tons of courses may a lot of time to
            // complete an evaluation.
            if (!empty($this->options['evaluation']) && !empty($this->options['reuseprevanalysed'])) {

                $previousanalysis = \core_analytics\dataset_manager::get_evaluation_analysable_file($this->modelid,
                    $analysable->get_id(), $timesplitting->get_id());
                // 1 week is a partly random time interval, no need to worry about DST.
                $boundary = time() - WEEKSECS;
                if ($previousanalysis && $previousanalysis->get_timecreated() > $boundary) {
                    // Recover the previous analysed file and avoid generating a new one.

                    // Don't bother filling a result object as it is only useful when there are no files generated.
                    $files[$timesplitting->get_id()] = $previousanalysis;
                    continue;
                }
            }

            if ($includetarget) {
                $result = $this->process_time_splitting($timesplitting, $analysable, $target);
            } else {
                $result = $this->process_time_splitting($timesplitting, $analysable);
            }

            if (!empty($result->file)) {
                $files[$timesplitting->get_id()] = $result->file;
            }
            $results[] = $result;
        }

        if (empty($files)) {
            $errors = array();
            foreach ($results as $timesplittingid => $result) {
                $errors[] = $timesplittingid . ': ' . $result->message;
            }

            $a = new \stdClass();
            $a->analysableid = $analysable->get_id();
            $a->errors = implode(', ', $errors);
            $this->add_log(get_string('analysablenotused', 'analytics', $a));
        }

        return $files;
    }

    /**
     * Adds a register to the analysis log.
     *
     * @param string $string
     * @return void
     */
    public function add_log($string) {
        $this->log[] = $string;
    }

    /**
     * Returns the analysis logs.
     *
     * @return string[]
     */
    public function get_logs() {
        return $this->log;
    }

    /**
     * Processes the analysable samples using the provided time splitting method.
     *
     * @param \core_analytics\local\time_splitting\base $timesplitting
     * @param \core_analytics\analysable $analysable
     * @param \core_analytics\local\target\base|false $target
     * @return \stdClass Results object.
     */
    protected function process_time_splitting($timesplitting, $analysable, $target = false) {

        $result = new \stdClass();

        if (!$timesplitting->is_valid_analysable($analysable)) {
            $result->status = \core_analytics\model::ANALYSABLE_REJECTED_TIME_SPLITTING_METHOD;
            $result->message = get_string('invalidanalysablefortimesplitting', 'analytics',
                $timesplitting->get_name());
            return $result;
        }
        $timesplitting->set_analysable($analysable);

        if (CLI_SCRIPT && !PHPUNIT_TEST) {
            mtrace('Analysing id "' . $analysable->get_id() . '" with "' . $timesplitting->get_name() .
                '" time splitting method...');
        }

        // What is a sample is defined by the analyser, it can be an enrolment, a course, a user, a question
        // attempt... it is on what we will base indicators calculations.
        list($sampleids, $samplesdata) = $this->get_all_samples($analysable);

        if (count($sampleids) === 0) {
            $result->status = \core_analytics\model::ANALYSABLE_REJECTED_TIME_SPLITTING_METHOD;
            $result->message = get_string('nodata', 'analytics');
            return $result;
        }

        if ($target) {
            // All ranges are used when we are calculating data for training.
            $ranges = $timesplitting->get_all_ranges();
        } else {
            // Only some ranges can be used for prediction (it depends on the time range where we are right now).
            $ranges = $this->get_prediction_ranges($timesplitting);
        }

        // There is no need to keep track of the evaluated samples and ranges as we always evaluate the whole dataset.
        if ($this->options['evaluation'] === false) {

            if (empty($ranges)) {
                $result->status = \core_analytics\model::ANALYSABLE_REJECTED_TIME_SPLITTING_METHOD;
                $result->message = get_string('nonewdata', 'analytics');
                return $result;
            }

            // We skip all samples that are already part of a training dataset, even if they have noe been used for training yet.
            $sampleids = $this->filter_out_train_samples($sampleids, $timesplitting);

            if (count($sampleids) === 0) {
                $result->status = \core_analytics\model::ANALYSABLE_REJECTED_TIME_SPLITTING_METHOD;
                $result->message = get_string('nonewdata', 'analytics');
                return $result;
            }

            // Only when processing data for predictions.
            if ($target === false) {
                // We also filter out ranges that have already been used for predictions.
                $ranges = $this->filter_out_prediction_ranges($ranges, $timesplitting);
            }

            if (count($ranges) === 0) {
                $result->status = \core_analytics\model::ANALYSABLE_REJECTED_TIME_SPLITTING_METHOD;
                $result->message = get_string('nonewtimeranges', 'analytics');
                return $result;
            }
        }

        $dataset = new \core_analytics\dataset_manager($this->modelid, $analysable->get_id(), $timesplitting->get_id(),
            $this->options['evaluation'], !empty($target));

        // Flag the model + analysable + timesplitting as being analysed (prevent concurrent executions).
        if (!$dataset->init_process()) {
            // If this model + analysable + timesplitting combination is being analysed we skip this process.
            $result->status = \core_analytics\model::NO_DATASET;
            $result->message = get_string('analysisinprogress', 'analytics');
            return $result;
        }

        // Remove samples the target consider invalid. Note that we use $this->target, $target will be false
        // during prediction, but we still need to discard samples the target considers invalid.
        $this->target->add_sample_data($samplesdata);
        $this->target->filter_out_invalid_samples($sampleids, $analysable, $target);

        if (!$sampleids) {
            $result->status = \core_analytics\model::NO_DATASET;
            $result->message = get_string('novalidsamples', 'analytics');
            $dataset->close_process();
            return $result;
        }

        foreach ($this->indicators as $key => $indicator) {
            // The analyser attaches the main entities the sample depends on and are provided to the
            // indicator to calculate the sample.
            $this->indicators[$key]->add_sample_data($samplesdata);
        }
        // Provide samples to the target instance (different than $this->target) $target is the new instance we get
        // for each analysis in progress.
        if ($target) {
            $target->add_sample_data($samplesdata);
        }

        // Here we start the memory intensive process that will last until $data var is
        // unset (until the method is finished basically).
        $data = $timesplitting->calculate($sampleids, $this->get_samples_origin(), $this->indicators, $ranges, $target);

        if (!$data) {
            $result->status = \core_analytics\model::ANALYSABLE_REJECTED_TIME_SPLITTING_METHOD;
            $result->message = get_string('novaliddata', 'analytics');
            $dataset->close_process();
            return $result;
        }

        // Write all calculated data to a file.
        $file = $dataset->store($data);

        // Flag the model + analysable + timesplitting as analysed.
        $dataset->close_process();

        // No need to keep track of analysed stuff when evaluating.
        if ($this->options['evaluation'] === false) {
            // Save the samples that have been already analysed so they are not analysed again in future.

            if ($target) {
                $this->save_train_samples($sampleids, $timesplitting, $file);
            } else {
                $this->save_prediction_ranges($ranges, $timesplitting);
            }
        }

        $result->status = \core_analytics\model::OK;
        $result->message = get_string('successfullyanalysed', 'analytics');
        $result->file = $file;
        return $result;
    }

    /**
     * Returns the ranges of a time splitting that can be used to predict.
     *
     * @param \core_analytics\local\time_splitting\base $timesplitting
     * @return array
     */
    protected function get_prediction_ranges($timesplitting) {

        $now = time();

        // We already provided the analysable to the time splitting method, there is no need to feed it back.
        $predictionranges = array();
        foreach ($timesplitting->get_all_ranges() as $rangeindex => $range) {
            if ($timesplitting->ready_to_predict($range)) {
                // We need to maintain the same indexes.
                $predictionranges[$rangeindex] = $range;
            }
        }

        return $predictionranges;
    }

    /**
     * Filters out samples that have already been used for training.
     *
     * @param int[] $sampleids
     * @param \core_analytics\local\time_splitting\base $timesplitting
     * @return int[]
     */
    protected function filter_out_train_samples($sampleids, $timesplitting) {
        global $DB;

        $params = array('modelid' => $this->modelid, 'analysableid' => $timesplitting->get_analysable()->get_id(),
            'timesplitting' => $timesplitting->get_id());

        $trainingsamples = $DB->get_records('analytics_train_samples', $params);

        // Skip each file trained samples.
        foreach ($trainingsamples as $trainingfile) {

            $usedsamples = json_decode($trainingfile->sampleids, true);

            if (!empty($usedsamples)) {
                // Reset $sampleids to $sampleids minus this file's $usedsamples.
                $sampleids = array_diff_key($sampleids, $usedsamples);
            }
        }

        return $sampleids;
    }

    /**
     * Filters out samples that have already been used for prediction.
     *
     * @param array $ranges
     * @param \core_analytics\local\time_splitting\base $timesplitting
     * @return int[]
     */
    protected function filter_out_prediction_ranges($ranges, $timesplitting) {
        global $DB;

        $params = array('modelid' => $this->modelid, 'analysableid' => $timesplitting->get_analysable()->get_id(),
            'timesplitting' => $timesplitting->get_id());

        $predictedranges = $DB->get_records('analytics_predict_ranges', $params);
        foreach ($predictedranges as $predictedrange) {
            if (!empty($ranges[$predictedrange->rangeindex])) {
                unset($ranges[$predictedrange->rangeindex]);
            }
        }

        return $ranges;

    }

    /**
     * Saves samples that have just been used for training.
     *
     * @param int[] $sampleids
     * @param \core_analytics\local\time_splitting\base $timesplitting
     * @param \stored_file $file
     * @return bool
     */
    protected function save_train_samples($sampleids, $timesplitting, $file) {
        global $DB;

        $trainingsamples = new \stdClass();
        $trainingsamples->modelid = $this->modelid;
        $trainingsamples->analysableid = $timesplitting->get_analysable()->get_id();
        $trainingsamples->timesplitting = $timesplitting->get_id();
        $trainingsamples->fileid = $file->get_id();

        $trainingsamples->sampleids = json_encode($sampleids);
        $trainingsamples->timecreated = time();

        return $DB->insert_record('analytics_train_samples', $trainingsamples);
    }

    /**
     * Saves samples that have just been used for prediction.
     *
     * @param array $ranges
     * @param \core_analytics\local\time_splitting\base $timesplitting
     * @return void
     */
    protected function save_prediction_ranges($ranges, $timesplitting) {
        global $DB;

        $predictionrange = new \stdClass();
        $predictionrange->modelid = $this->modelid;
        $predictionrange->analysableid = $timesplitting->get_analysable()->get_id();
        $predictionrange->timesplitting = $timesplitting->get_id();
        $predictionrange->timecreated = time();

        foreach ($ranges as $rangeindex => $unused) {
            $predictionrange->rangeindex = $rangeindex;
            $DB->insert_record('analytics_predict_ranges', $predictionrange);
        }
    }
}
