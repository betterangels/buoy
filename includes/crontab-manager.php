<?php
/**
 * A simple cron management utility.
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\Buoy_Crontab_Manager
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * A simple helper class to manage crontab files.
 *
 * @todo Test code coverage, pls kthxbai.
 */
class Buoy_Crontab_Manager {

    /**
     * The current crontab file contents.
     *
     * @var string[]
     */
    public $crontab_lines = array();

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct () {
        if ($this->crontabExists()) {
            $this->crontab_lines = $this->readCrontab();
        }
    }

    /**
     * Gets the current crontab file contents.
     *
     * @return string[]
     */
    public function getCron () {
        return $this->crontab_lines;
    }

    /**
     * Checks whether there is an entry for the job in the crontab.
     *
     * @param string $job
     *
     * @return bool
     */
    public function jobExists ($job) {
        foreach ($this->getCron() as $line) {
            if (strpos($line, $job)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether a crontab file exists.
     *
     * @return bool
     */
    private function crontabExists () {
        system('crontab -l >/dev/null 2>&1', $ret_val);
        return (0 === $ret_val) ? true : false;
    }

    /**
     * Reads the current crontab file from disk.
     *
     * @return string[]
     */
    private function readCrontab () {
        return array_filter(explode(PHP_EOL, shell_exec('crontab -l 2>/dev/null')));
    }

    /**
     * Add cron jobs to the current crontab.
     *
     * @param string|string[] $jobs Cron jobs to add.
     *
     * @return Buoy_Crontab_Manager
     */
    public function appendCronJobs ($jobs) {
        if (is_string($jobs)) { $jobs = array($jobs); }
        foreach ($jobs as $job) {
            $this->crontab_lines[] = $job;
        }
        return $this;
    }

    /**
     * Remove cron jobs from the current crontab.
     *
     * @param string $job_patterns Regex pattern of cron jobs to remove.
     *
     * @return Buoy_Crontab_Manager
     */
    public function removeCronJobs ($job_patterns) {
        if (is_string($job_patterns)) { $job_patterns = array($job_patterns); }
        $original_count = count($this->crontab_lines);
        if (0 !== $original_count) {
            foreach ($job_patterns as $pat) {
                $cron_array = preg_grep($pat, $this->crontab_lines, PREG_GREP_INVERT);
            }
            if ($original_count !== count($cron_array)) {
                $this->crontab_lines = $cron_array;
                return $this->removeCrontab()->save();
            }
        }
    }

    /**
     * Deletes the crontab file from the filesystem.
     *
     * @return Buoy_Crontab_Manager
     */
    private function removeCrontab () {
        system('crontab -r');
        return $this;
    }

    /**
     * Saves the current crontab contents to the filesystem.
     *
     * @return Buoy_Crontab_Manager
     *
     * @throws RuntimeException If an error occurrs during crontab installation.
     */
    public function save () {
        $t = tempnam(sys_get_temp_dir(), 'temp_cron');
        file_put_contents($t, implode(PHP_EOL, $this->crontab_lines) . PHP_EOL);
        ob_start(); // prevent output (mostly for DreamHost)
        $out = system('crontab ' . escapeshellarg($t), $ret_val);
        if (0 === $ret_val) {
            unlink($t);
        } else if (false !== strpos($out, 'http://wiki.dreamhost.com/Crontab#MAILTO_variable_requirement')) {
            $cmd = sprintf(
                'expect %s %s',
                escapeshellarg(dirname(__FILE__).'/dreamhost_cron.exp'),
                escapeshellarg($t)
            );
            system($cmd, $s);
            if (0 === $s) {
                unlink($t);
            } else {
                // TODO: Throw some kind of DreamHost specific error.
                //       That way, we can show an admin notice to the
                //       user back on the WordPress side. Phew!
            }
        } else {
            $php_usr = posix_getpwuid(posix_geteuid());
            throw new RuntimeException('Failed to install crontab for ' . $php_usr['name']);
        }
        @ob_end_clean();
        return $this;
    }
}
