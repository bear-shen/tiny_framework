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
            echo $job . "\r\n";
            try {
                list($jobClass, $jobData) = json_decode($job, true);
                (new $jobClass())->handle($jobData);
            } catch (\Throwable $ex) {
                //@see exceptionHandler
                $file     = $ex->getFile();
                $traceStr = "------------------ Err ------------------\r\n" .
                            ":: " . $ex->getCode() . ":" . $ex->getMessage() . "\r\n" .
                            ":: " . $file . ":" . $ex->getLine() . "\r\n";
                echo $traceStr;
            }
        }
    }

    public static function push($class, $data) {
        $cacheKey = Settings::get('job.key');
        global $cache;
        $cache->rpush($cacheKey, [json_encode([$class, $data], JSON_UNESCAPED_UNICODE)]);
    }
}