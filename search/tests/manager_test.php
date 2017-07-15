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
 * Search manager unit tests.
 *
 * @package     core_search
 * @category    phpunit
 * @copyright   2015 David Monllao {@link http://www.davidmonllao.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/testable_core_search.php');
require_once(__DIR__ . '/fixtures/mock_search_area.php');

/**
 * Unit tests for search manager.
 *
 * @package     core_search
 * @category    phpunit
 * @copyright   2015 David Monllao {@link http://www.davidmonllao.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_manager_testcase extends advanced_testcase {

    protected $forumpostareaid = null;
    protected $mycoursesareaid = null;

    public function setUp() {
        $this->forumpostareaid = \core_search\manager::generate_areaid('mod_forum', 'post');
        $this->mycoursesareaid = \core_search\manager::generate_areaid('core_course', 'mycourse');
    }

    public function test_search_enabled() {

        $this->resetAfterTest();

        // Disabled by default.
        $this->assertFalse(\core_search\manager::is_global_search_enabled());

        set_config('enableglobalsearch', true);
        $this->assertTrue(\core_search\manager::is_global_search_enabled());

        set_config('enableglobalsearch', false);
        $this->assertFalse(\core_search\manager::is_global_search_enabled());
    }

    public function test_search_areas() {
        global $CFG;

        $this->resetAfterTest();

        set_config('enableglobalsearch', true);

        $fakeareaid = \core_search\manager::generate_areaid('mod_unexisting', 'chihuaquita');

        $searcharea = \core_search\manager::get_search_area($this->forumpostareaid);
        $this->assertInstanceOf('\core_search\base', $searcharea);

        $this->assertFalse(\core_search\manager::get_search_area($fakeareaid));

        $this->assertArrayHasKey($this->forumpostareaid, \core_search\manager::get_search_areas_list());
        $this->assertArrayNotHasKey($fakeareaid, \core_search\manager::get_search_areas_list());

        // Enabled by default once global search is enabled.
        $this->assertArrayHasKey($this->forumpostareaid, \core_search\manager::get_search_areas_list(true));

        list($componentname, $varname) = $searcharea->get_config_var_name();
        set_config($varname . '_enabled', 0, $componentname);
        \core_search\manager::clear_static();

        $this->assertArrayNotHasKey('mod_forum', \core_search\manager::get_search_areas_list(true));

        set_config($varname . '_enabled', 1, $componentname);

        // Although the result is wrong, we want to check that \core_search\manager::get_search_areas_list returns cached results.
        $this->assertArrayNotHasKey($this->forumpostareaid, \core_search\manager::get_search_areas_list(true));

        // Now we check the real result.
        \core_search\manager::clear_static();
        $this->assertArrayHasKey($this->forumpostareaid, \core_search\manager::get_search_areas_list(true));
    }

    public function test_search_config() {

        $this->resetAfterTest();

        $search = testable_core_search::instance();

        // We should test both plugin types and core subsystems. No core subsystems available yet.
        $searcharea = $search->get_search_area($this->forumpostareaid);

        list($componentname, $varname) = $searcharea->get_config_var_name();

        // Just with a couple of vars should be enough.
        $start = time() - 100;
        $end = time();
        set_config($varname . '_indexingstart', $start, $componentname);
        set_config($varname . '_indexingend', $end, $componentname);

        $configs = $search->get_areas_config(array($this->forumpostareaid => $searcharea));
        $this->assertEquals($start, $configs[$this->forumpostareaid]->indexingstart);
        $this->assertEquals($end, $configs[$this->forumpostareaid]->indexingend);
        $this->assertEquals(false, $configs[$this->forumpostareaid]->partial);

        try {
            $fakeareaid = \core_search\manager::generate_areaid('mod_unexisting', 'chihuaquita');
            $search->reset_config($fakeareaid);
            $this->fail('An exception should be triggered if the provided search area does not exist.');
        } catch (moodle_exception $ex) {
            $this->assertContains($fakeareaid . ' search area is not available.', $ex->getMessage());
        }

        // We clean it all but enabled components.
        $search->reset_config($this->forumpostareaid);
        $config = $searcharea->get_config();
        $this->assertEquals(1, $config[$varname . '_enabled']);
        $this->assertEquals(0, $config[$varname . '_indexingstart']);
        $this->assertEquals(0, $config[$varname . '_indexingend']);
        $this->assertEquals(0, $config[$varname . '_lastindexrun']);
        $this->assertEquals(0, $config[$varname . '_partial']);
        // No caching.
        $configs = $search->get_areas_config(array($this->forumpostareaid => $searcharea));
        $this->assertEquals(0, $configs[$this->forumpostareaid]->indexingstart);
        $this->assertEquals(0, $configs[$this->forumpostareaid]->indexingend);

        set_config($varname . '_indexingstart', $start, $componentname);
        set_config($varname . '_indexingend', $end, $componentname);

        // All components config should be reset.
        $search->reset_config();
        $this->assertEquals(0, get_config($componentname, $varname . '_indexingstart'));
        $this->assertEquals(0, get_config($componentname, $varname . '_indexingend'));
        $this->assertEquals(0, get_config($componentname, $varname . '_lastindexrun'));
        // No caching.
        $configs = $search->get_areas_config(array($this->forumpostareaid => $searcharea));
        $this->assertEquals(0, $configs[$this->forumpostareaid]->indexingstart);
        $this->assertEquals(0, $configs[$this->forumpostareaid]->indexingend);
    }

    /**
     * Tests the get_last_indexing_duration method in the base area class.
     */
    public function test_get_last_indexing_duration() {
        $this->resetAfterTest();

        $search = testable_core_search::instance();

        $searcharea = $search->get_search_area($this->forumpostareaid);

        // When never indexed, the duration is false.
        $this->assertSame(false, $searcharea->get_last_indexing_duration());

        // Set the start/end times.
        list($componentname, $varname) = $searcharea->get_config_var_name();
        $start = time() - 100;
        $end = time();
        set_config($varname . '_indexingstart', $start, $componentname);
        set_config($varname . '_indexingend', $end, $componentname);

        // The duration should now be 100.
        $this->assertSame(100, $searcharea->get_last_indexing_duration());
    }

    /**
     * Tests that partial indexing works correctly.
     */
    public function test_partial_indexing() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course and a forum.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum = $generator->create_module('forum', ['course' => $course->id]);

        // Index everything up to current. Ensure the course is older than current second so it
        // definitely doesn't get indexed again next time.
        $this->waitForSecond();
        $search = testable_core_search::instance();
        $search->index(false, 0);

        $searcharea = $search->get_search_area($this->forumpostareaid);
        list($componentname, $varname) = $searcharea->get_config_var_name();
        $this->assertFalse(get_config($componentname, $varname . '_partial'));

        // Add 3 discussions to the forum.
        $now = time();
        $generator->get_plugin_generator('mod_forum')->create_discussion(['course' => $course->id,
                'forum' => $forum->id, 'userid' => $USER->id, 'timemodified' => $now,
                'name' => 'Frog']);
        $generator->get_plugin_generator('mod_forum')->create_discussion(['course' => $course->id,
                'forum' => $forum->id, 'userid' => $USER->id, 'timemodified' => $now + 1,
                'name' => 'Toad']);
        $generator->get_plugin_generator('mod_forum')->create_discussion(['course' => $course->id,
                'forum' => $forum->id, 'userid' => $USER->id, 'timemodified' => $now + 2,
                'name' => 'Zombie']);
        time_sleep_until($now + 3);

        // Clear the count of added documents.
        $search->get_engine()->get_and_clear_added_documents();

        // Make the search engine delay while indexing each document.
        $search->get_engine()->set_add_delay(1.2);

        // Index with a limit of 2 seconds - it should index 2 of the documents (after the second
        // one, it will have taken 2.4 seconds so it will stop).
        $search->index(false, 2);
        $added = $search->get_engine()->get_and_clear_added_documents();
        $this->assertCount(2, $added);
        $this->assertEquals('Frog', $added[0]->get('title'));
        $this->assertEquals('Toad', $added[1]->get('title'));
        $this->assertEquals(1, get_config($componentname, $varname . '_partial'));

        // Add a label.
        $generator->create_module('label', ['course' => $course->id, 'intro' => 'Vampire']);

        // Wait to next second (so as to not reindex the label more than once, as it will now
        // be timed before the indexing run).
        $this->waitForSecond();

        // Next index with 1 second limit should do the label and not the forum - the logic is,
        // if it spent ages indexing an area last time, do that one last on next run.
        $search->index(false, 1);
        $added = $search->get_engine()->get_and_clear_added_documents();
        $this->assertCount(1, $added);
        $this->assertEquals('Vampire', $added[0]->get('title'));

        // Index again with a 2 second limit - it will redo last post for safety (because of other
        // things possibly having the same time second), and then do the remaining one. (Note:
        // because it always does more than one second worth of items, it would actually index 2
        // posts even if the limit were less than 2.)
        $search->index(false, 2);
        $added = $search->get_engine()->get_and_clear_added_documents();
        $this->assertCount(2, $added);
        $this->assertEquals('Toad', $added[0]->get('title'));
        $this->assertEquals('Zombie', $added[1]->get('title'));
        $this->assertFalse(get_config($componentname, $varname . '_partial'));

        // Index again - there should be nothing to index this time.
        $search->index(false, 2);
        $added = $search->get_engine()->get_and_clear_added_documents();
        $this->assertCount(0, $added);
        $this->assertFalse(get_config($componentname, $varname . '_partial'));
    }

    /**
     * Adding this test here as get_areas_user_accesses process is the same, results just depend on the context level.
     *
     * @return void
     */
    public function test_search_user_accesses() {
        global $DB;

        $this->resetAfterTest();

        $frontpage = $DB->get_record('course', array('id' => SITEID));
        $course1 = $this->getDataGenerator()->create_course();
        $course1ctx = context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course();
        $course2ctx = context_course::instance($course2->id);
        $teacher = $this->getDataGenerator()->create_user();
        $teacherctx = context_user::instance($teacher->id);
        $student = $this->getDataGenerator()->create_user();
        $studentctx = context_user::instance($student->id);
        $noaccess = $this->getDataGenerator()->create_user();
        $noaccessctx = context_user::instance($noaccess->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($student->id, $course1->id, 'student');

        $frontpageforum = $this->getDataGenerator()->create_module('forum', array('course' => $frontpage->id));
        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id));
        $forum3 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id));
        $frontpageforumcontext = context_module::instance($frontpageforum->cmid);
        $context1 = context_module::instance($forum1->cmid);
        $context2 = context_module::instance($forum2->cmid);
        $context3 = context_module::instance($forum3->cmid);

        $search = testable_core_search::instance();
        $mockareaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');
        $search->add_core_search_areas();
        $search->add_search_area($mockareaid, new core_mocksearch\search\mock_search_area());

        $this->setAdminUser();
        $this->assertTrue($search->get_areas_user_accesses());

        $sitectx = \context_course::instance(SITEID);
        $systemctxid = \context_system::instance()->id;

        // Can access the frontpage ones.
        $this->setUser($noaccess);
        $contexts = $search->get_areas_user_accesses();
        $this->assertEquals(array($frontpageforumcontext->id => $frontpageforumcontext->id), $contexts[$this->forumpostareaid]);
        $this->assertEquals(array($sitectx->id => $sitectx->id), $contexts[$this->mycoursesareaid]);
        $mockctxs = array($noaccessctx->id => $noaccessctx->id, $systemctxid => $systemctxid);
        $this->assertEquals($mockctxs, $contexts[$mockareaid]);

        $this->setUser($teacher);
        $contexts = $search->get_areas_user_accesses();
        $frontpageandcourse1 = array($frontpageforumcontext->id => $frontpageforumcontext->id, $context1->id => $context1->id,
            $context2->id => $context2->id);
        $this->assertEquals($frontpageandcourse1, $contexts[$this->forumpostareaid]);
        $this->assertEquals(array($sitectx->id => $sitectx->id, $course1ctx->id => $course1ctx->id),
            $contexts[$this->mycoursesareaid]);
        $mockctxs = array($teacherctx->id => $teacherctx->id, $systemctxid => $systemctxid);
        $this->assertEquals($mockctxs, $contexts[$mockareaid]);

        $this->setUser($student);
        $contexts = $search->get_areas_user_accesses();
        $this->assertEquals($frontpageandcourse1, $contexts[$this->forumpostareaid]);
        $this->assertEquals(array($sitectx->id => $sitectx->id, $course1ctx->id => $course1ctx->id),
            $contexts[$this->mycoursesareaid]);
        $mockctxs = array($studentctx->id => $studentctx->id, $systemctxid => $systemctxid);
        $this->assertEquals($mockctxs, $contexts[$mockareaid]);

        // Hide the activity.
        set_coursemodule_visible($forum2->cmid, 0);
        $contexts = $search->get_areas_user_accesses();
        $this->assertEquals(array($frontpageforumcontext->id => $frontpageforumcontext->id, $context1->id => $context1->id),
            $contexts[$this->forumpostareaid]);

        // Now test course limited searches.
        set_coursemodule_visible($forum2->cmid, 1);
        $this->getDataGenerator()->enrol_user($student->id, $course2->id, 'student');
        $contexts = $search->get_areas_user_accesses();
        $allcontexts = array($frontpageforumcontext->id => $frontpageforumcontext->id, $context1->id => $context1->id,
            $context2->id => $context2->id, $context3->id => $context3->id);
        $this->assertEquals($allcontexts, $contexts[$this->forumpostareaid]);
        $this->assertEquals(array($sitectx->id => $sitectx->id, $course1ctx->id => $course1ctx->id,
            $course2ctx->id => $course2ctx->id), $contexts[$this->mycoursesareaid]);

        $contexts = $search->get_areas_user_accesses(array($course1->id, $course2->id));
        $allcontexts = array($context1->id => $context1->id, $context2->id => $context2->id, $context3->id => $context3->id);
        $this->assertEquals($allcontexts, $contexts[$this->forumpostareaid]);
        $this->assertEquals(array($course1ctx->id => $course1ctx->id,
            $course2ctx->id => $course2ctx->id), $contexts[$this->mycoursesareaid]);

        $contexts = $search->get_areas_user_accesses(array($course2->id));
        $allcontexts = array($context3->id => $context3->id);
        $this->assertEquals($allcontexts, $contexts[$this->forumpostareaid]);
        $this->assertEquals(array($course2ctx->id => $course2ctx->id), $contexts[$this->mycoursesareaid]);

        $contexts = $search->get_areas_user_accesses(array($course1->id));
        $allcontexts = array($context1->id => $context1->id, $context2->id => $context2->id);
        $this->assertEquals($allcontexts, $contexts[$this->forumpostareaid]);
        $this->assertEquals(array($course1ctx->id => $course1ctx->id), $contexts[$this->mycoursesareaid]);
    }

    /**
     * Tests the block support in get_search_user_accesses.
     *
     * @return void
     */
    public function test_search_user_accesses_blocks() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and add HTML block.
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $context1 = \context_course::instance($course1->id);
        $page = new \moodle_page();
        $page->set_context($context1);
        $page->set_course($course1);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->blocks->load_blocks();
        $page->blocks->add_block_at_end_of_default_region('html');

        // Create another course with HTML blocks only in some weird page or a module page (not
        // yet supported, so both these blocks will be ignored).
        $course2 = $generator->create_course();
        $context2 = \context_course::instance($course2->id);
        $page = new \moodle_page();
        $page->set_context($context2);
        $page->set_course($course2);
        $page->set_pagelayout('standard');
        $page->set_pagetype('bogus-page');
        $page->blocks->load_blocks();
        $page->blocks->add_block_at_end_of_default_region('html');

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id));
        $forumcontext = context_module::instance($forum->cmid);
        $page = new \moodle_page();
        $page->set_context($forumcontext);
        $page->set_course($course2);
        $page->set_pagelayout('standard');
        $page->set_pagetype('mod-forum-view');
        $page->blocks->load_blocks();
        $page->blocks->add_block_at_end_of_default_region('html');

        // The third course has 2 HTML blocks.
        $course3 = $generator->create_course();
        $context3 = \context_course::instance($course3->id);
        $page = new \moodle_page();
        $page->set_context($context3);
        $page->set_course($course3);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->blocks->load_blocks();
        $page->blocks->add_block_at_end_of_default_region('html');
        $page->blocks->add_block_at_end_of_default_region('html');

        // Student 1 belongs to all 3 courses.
        $student1 = $generator->create_user();
        $generator->enrol_user($student1->id, $course1->id, 'student');
        $generator->enrol_user($student1->id, $course2->id, 'student');
        $generator->enrol_user($student1->id, $course3->id, 'student');

        // Student 2 belongs only to course 2.
        $student2 = $generator->create_user();
        $generator->enrol_user($student2->id, $course2->id, 'student');

        // And the third student is only in course 3.
        $student3 = $generator->create_user();
        $generator->enrol_user($student3->id, $course3->id, 'student');

        $search = testable_core_search::instance();
        $search->add_core_search_areas();

        // Admin gets 'true' result to function regardless of blocks.
        $this->setAdminUser();
        $this->assertTrue($search->get_areas_user_accesses());

        // Student 1 gets all 3 block contexts.
        $this->setUser($student1);
        $contexts = $search->get_areas_user_accesses();
        $this->assertArrayHasKey('block_html-content', $contexts);
        $this->assertCount(3, $contexts['block_html-content']);

        // Student 2 does not get any blocks.
        $this->setUser($student2);
        $contexts = $search->get_areas_user_accesses();
        $this->assertArrayNotHasKey('block_html-content', $contexts);

        // Student 3 gets only two of them.
        $this->setUser($student3);
        $contexts = $search->get_areas_user_accesses();
        $this->assertArrayHasKey('block_html-content', $contexts);
        $this->assertCount(2, $contexts['block_html-content']);

        // A course limited search for student 1 is the same as the student 3 search.
        $this->setUser($student1);
        $limitedcontexts = $search->get_areas_user_accesses([$course3->id]);
        $this->assertEquals($contexts['block_html-content'], $limitedcontexts['block_html-content']);
    }

    /**
     * test_is_search_area
     *
     * @return void
     */
    public function test_is_search_area() {

        $this->assertFalse(testable_core_search::is_search_area('\asd\asd'));
        $this->assertFalse(testable_core_search::is_search_area('\mod_forum\search\posta'));
        $this->assertFalse(testable_core_search::is_search_area('\core_search\base_mod'));
        $this->assertTrue(testable_core_search::is_search_area('\mod_forum\search\post'));
        $this->assertTrue(testable_core_search::is_search_area('\\mod_forum\\search\\post'));
        $this->assertTrue(testable_core_search::is_search_area('mod_forum\\search\\post'));
    }
}
