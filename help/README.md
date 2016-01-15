# Buoy Help

This directory contains the contents for the Better Angels' Buoy on-screen help tabs and a screen's help sidebar. Its contents are dynamically loaded by the [WP Screen Help Loader](https://wordpress.org/plugins/wp-screen-help-loader/) plugin library.

* Each help file is contained within a directory matching the [locale string of the WordPress installation](https://developer.wordpress.org/reference/functions/get_locale/).
* File contents are standard [Markdown](https://daringfireball.net/projects/markdown/), and parsed into HTML at runtime.
* File names reference the `$action` and `$id` members of the [`WP_Screen`](https://developer.wordpress.org/reference/classes/wp_screen/) class.
* Files can be optionally suffixed with an optional numeric priority (lower numbers display first, above the content of files with larger numbers as per `WP_Screen` documentation).
* Files can be optionally prefixed with `sidebar-` indicating that the file contains content intended for the help sidebar rather than a tab of its own.
* The special filename `sidebar.md` is appended to the sidebar on every WordPress admin screen page where on-screen help is shown.

For instance, take the following directory structure:

    help/
    ├── README.md # <- You are reading this file now.
    └── en_US
        ├── addbuoy_team-10.md
        ├── buoy_team_page_buoy_safety_info.md
        ├── buoy_team_page_buoy_team_membership-10.md
        ├── buoy_team_page_buoy_team_membership-20.md
        ├── edit-buoy_team-10.md
        ├── profile-20.md
        ├── settings_page_buoy_settings.md
        ├── sidebar-profile.md
        └── sidebar.md

This provides seven different help tabs on six different admin pages when WordPress is configured to use the `en_US` locale (American English). The two tabs on the "team membership" page are numerically sorted by applying a suffix: `-10` comes before `-20`, and so on. If a priority suffix is not present, that tab uses the default value set by WordPress (`10`). The "profile" page specifies a priority of `20` so it appears after any help tabs added by WordPress itself already on that page. Additionally, the "profile" page will have extra content added to its screen's help sidebar.

The contents of `sidebar.md` will appear in the help sidebar wherever on-screen help is shown.
