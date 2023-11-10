<?php

namespace App\Logging;

use Monolog\Logger;

class JobsLogger
{
    /**
     * Create a custom Monolog instance.
     *
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config){
        $logger = new Logger("JobsLoggingHandler");
        return $logger->pushHandler(new JobsLoggingHandler());
    }
}