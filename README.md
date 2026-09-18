# Time Tracker block for Moodle

Shows tracked time from the companion [Time Tracker](https://github.com/mooplugins/moodle-local_timetracker) plugin (`local_timetracker`) on course and activity pages.

## Features

- **Learners:** list of enabled activities with tracked time (hh:mm:ss) plus a course total
- **Teachers / managers:** course total and average per enrolled learner, with a link to the Time Tracker report (when permitted)
- Works on course home and activity (`mod`) pages
- Stores no personal data of its own (Privacy API null provider)

## Requirements

- Moodle 4.5 or later (CI tested on 4.5, 5.0, and 5.2)
- [`local_timetracker`](https://github.com/mooplugins/moodle-local_timetracker) **1.4.4** or later (`2026091800+`)

## Installation

1. Install and enable **Time Tracker** (`local/timetracker`) first.
2. Copy this plugin into `blocks/timetracker`.
3. Visit **Site administration → Notifications** and complete the installation.
4. Enable Time Tracker on one or more activities in a course.
5. Turn editing on and add the **Time Tracker** block to the course or an activity.

## Privacy

This block does not store personal data. It only displays totals from `local_timetracker`.

## Changelog

See [CHANGES.md](CHANGES.md).

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).

## Credits

Originally developed for [ScholarLMS](https://www.scholarlms.com/). Maintained by [MooPlugins](https://www.mooplugins.com/).
