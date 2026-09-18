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
 * Block for displaying Time Tracker totals.
 *
 * @package    block_timetracker
 * @author     BitKea Technologies LLP
 * @copyright  2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Block for displaying Time Tracker totals.
 *
 * @package    block_timetracker
 * @author     BitKea Technologies LLP
 * @copyright  2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_timetracker extends block_base {
    /**
     * Sets the block title.
     */
    public function init() {
        $this->title = get_string('timetracker_title_default', 'block_timetracker');
    }

    /**
     * Where the block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'course-view' => true,
            'mod' => true,
        ];
    }

    /**
     * Instance config not used.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return false;
    }

    /**
     * No global settings page.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Format seconds as hh:mm:ss.
     *
     * @param int $seconds
     * @return string
     */
    protected function format_duration(int $seconds): string {
        $seconds = max(0, $seconds);
        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds, 60) % 60,
            $seconds % 60
        );
    }

    /**
     * Creates the block's main content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $CFG, $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($CFG->dirroot) || !file_exists($CFG->dirroot . '/local/timetracker/locallib.php')) {
            $this->content->text = get_string('missingplugin', 'block_timetracker');
            return $this->content;
        }

        require_once($CFG->dirroot . '/local/timetracker/locallib.php');
        local_timetracker_ensure_schema();

        if (!get_config('local_timetracker', 'enabled')) {
            $this->content->text = get_string('plugindisabled', 'block_timetracker');
            return $this->content;
        }

        $course = $this->page->course;
        if (empty($course->id) || (int) $course->id === (int) SITEID) {
            $this->content->text = get_string('nocourse', 'block_timetracker');
            return $this->content;
        }

        $coursecontext = context_course::instance($course->id);
        $modinfo = get_fast_modinfo($course);
        $cms = $modinfo->get_cms();

        // Teachers / managers: course roll-up + link to staff report.
        if (has_capability('moodle/course:update', $coursecontext)) {
            $totaltime = 0;
            if ($DB->get_manager()->table_exists(LOCAL_TIMETRACKER_REPORT_TABLE)) {
                $totaltime = (int) $DB->get_field_sql(
                    "SELECT COALESCE(SUM(timespent), 0)
                       FROM {" . LOCAL_TIMETRACKER_REPORT_TABLE . "}
                      WHERE course = ? AND userid <> ?",
                    [$course->id, guest_user()->id]
                );
            }
            // Include open/finished logs not yet compacted for enrolled users is heavy;
            // report table + staff report page is the source of truth for detail.
            $enrolledcount = count_enrolled_users($coursecontext);
            $average = $enrolledcount > 0 ? ($totaltime / $enrolledcount) : 0;

            $this->content->text = html_writer::tag(
                'p',
                get_string('message_admin', 'block_timetracker')
            );
            $this->content->text .= html_writer::div(
                html_writer::tag('strong', get_string('total_admin', 'block_timetracker'))
                . ': ' . html_writer::tag('i', $this->format_duration($totaltime))
            );
            $this->content->text .= html_writer::div(
                html_writer::tag('strong', get_string('avg_admin', 'block_timetracker'))
                . ': ' . html_writer::tag('i', $this->format_duration((int) $average))
            );
            $this->content->footer = html_writer::div(get_string('time_format', 'block_timetracker'));

            if (has_capability('local/timetracker:viewreport', context_system::instance())) {
                $reporturl = new moodle_url('/local/timetracker/report/index.php');
                $this->content->footer .= html_writer::div(
                    html_writer::link($reporturl, get_string('moredetails', 'block_timetracker'))
                );
            }

            return $this->content;
        }

        // Learners: per enabled activity + course total.
        $timetrackers = $DB->get_records(LOCAL_TIMETRACKER_TABLE, [
            'course' => $course->id,
            'enabled' => 1,
        ]);

        if (!$timetrackers) {
            $this->content->text = get_string('notrackers', 'block_timetracker');
            return $this->content;
        }

        $totaltime = 0;
        $lines = [];
        foreach ($timetrackers as $timetracker) {
            $cmid = (int) $timetracker->coursemodule;
            if (!isset($cms[$cmid]) || $cms[$cmid]->deletioninprogress) {
                continue;
            }
            if (!$cms[$cmid]->uservisible) {
                continue;
            }

            $timetracked = local_timetracker_get_timespent((int) $USER->id, (int) $timetracker->id);
            $totaltime += $timetracked;

            if ($timetracked > 0) {
                $lines[] = format_string($cms[$cmid]->name) . ': '
                    . html_writer::tag('i', $this->format_duration($timetracked));
            }
        }

        if ($lines) {
            $this->content->text = implode(html_writer::empty_tag('br'), $lines);
        } else {
            $this->content->text = get_string('notimesyet', 'block_timetracker');
        }

        $this->content->footer = html_writer::div(
            html_writer::tag('strong', get_string('total', 'block_timetracker'))
            . ': ' . html_writer::tag('i', $this->format_duration($totaltime))
        );
        $this->content->footer .= html_writer::div(get_string('time_format', 'block_timetracker'));

        return $this->content;
    }
}
