<?php namespace Job;

use Model\Settings;

class Kernel {
    public function handle() {
        $jobSettings = Settings::get('job');
        $key         = $jobSettings['key'];
        $interval    = $jobSettings['interval'];
        global $cache;
        while (true) {
            echo 'job running:' . microtime(true) . "\r\n";
            usleep($interval);
            $job = $cache->lpop($key);
            if (!$job) continue;
            list($jobClass, $jobData) = json_decode($job, true);
            (new $jobClass())->handle($jobData);
        }
    }

    public static function push($class, $data) {
        $cacheKey = Settings::get('job.key');
        global $cache;
        $cache->rpush($cacheKey, [json_encode([$class, $data], JSON_UNESCAPED_UNICODE)]);
    }
}