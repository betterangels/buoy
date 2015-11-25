<?php
/**
 * A simple helper class to manage crontab files.
 *
 * TODO: Test code coverage, pls kthxbai.
 */
class BuoyCrontabManager {
    public $crontab_lines = array();

    public function __construct () {
        if ($this->crontabExists()) {
            $this->crontab_lines = $this->readCrontab();
        }
    }

    public function getCron () {
        return $this->crontab_lines;
    }

    private function crontabExists () {
        system('crontab -l >/dev/null 2>&1', $ret_val);
        return (0 === $ret_val) ? true : false;
    }

    private function readCrontab () {
        return array_filter(explode(PHP_EOL, shell_exec('crontab -l 2>/dev/null')));
    }

    public function appendCronJobs ($jobs) {
        if (is_string($jobs)) { $jobs = array($jobs); }
        foreach ($jobs as $job) {
            $this->crontab_lines[] = $job;
        }
        return $this;
    }

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

    private function removeCrontab () {
        system('crontab -r');
        return $this;
    }

    public function save () {
        $t = tempnam(sys_get_temp_dir(), 'temp_cron');
        file_put_contents($t, implode(PHP_EOL, $this->crontab_lines) . PHP_EOL);
        system('crontab ' . escapeshellarg($t), $ret_val);
        if (0 === $ret_val) {
            unlink($t);
        } else {
            $php_usr = posix_getpwuid(posix_geteuid());
            throw new RuntimeException('Failed to install crontab for ' . $php_usr['name']);
        }
        return $this;
    }
}
