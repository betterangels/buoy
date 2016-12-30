<?php
/**
 * Buoy Error Codes.
 *
 * Error codes for Buoy are defined in a class in this file.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\Error_Codes
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Error codes shared throughout Buoy.
 *
 * This class mimics Linux's implementation of POSIX.1. See errno(3).
 * There's nothing special about these numbers except insofar as they
 * will be familiar to POSIX nerds (and Linux kernel hackers). That
 * said, they should not be confused with the actual system's errors.
 */
class Buoy_Error_Codes {

    /**
     * Error code for when a required system function is unavailable.
     *
     * @link https://git.kernel.org/cgit/linux/kernel/git/torvalds/linux.git/tree/include/uapi/asm-generic/errno.h#n10
     */
    const ENOSYS = 38;

}
