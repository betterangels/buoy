# Buoy Help

This directory contains markdown files for the Better Angels' Buoy on-screen help tabs.

Each help file is contained within a directory matching the locale string of the WordPress installation. The file names reference the ID of the [`WP_Screen`](https://developer.wordpress.org/reference/classes/wp_screen/) class's `$id` member, followed by an optional alphabetic sort order suffix. For instance, take the following directory structure:

    help/
    ├── README.md
    └── en_US
        ├── my-team_page_better-angels_confirm-guardianship-A.md
        ├── my-team_page_better-angels_confirm-guardianship-B.md
        ├── my-team_page_better-angels_safety-info.md
        ├── toplevel_page_better-angels_choose-angels-A.md
        └── toplevel_page_better-angels_choose-angels-B.md

This provides 5 different help tabs on three different admin pages when WordPress is configured to use the `en_US` locale (American English). The pages are alphabetically sorted by applying a suffix: `-A`, `-B`, etc. The alphabetic sort order determines the order in which the tabs appear on the screen in the WordPress UI; `-A` is before `-B` and so on.
