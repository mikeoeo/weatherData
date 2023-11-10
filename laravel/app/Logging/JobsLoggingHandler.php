<?php

namespace App\Logging;

use Monolog\LogRecord;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;

class JobsLoggingHandler extends AbstractProcessingHandler
{
    /**
     * @var string The table name
     */
    protected string $table;

    /**
     *
     * Reference:
     * https://github.com/markhilton/monolog-mysql/blob/master/src/Logger/Monolog/Handler/MysqlHandler.php
     */
    public function __construct($level = Level::Debug, $bubble = true)
    {
        $this->table = 'jobs_log';
        parent::__construct($level, $bubble);
    }
    
    protected function write(LogRecord $record):void
    {
        /*
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('data_providers_id')->nullable();
            $table->boolean('success_status');
            $table->longText('message');
            $table->timestamp('created_at');
         */
        $data = array(
            'location_id' => $record['context']['location_id'],
            'data_providers_id' => $record['context']['data_providers_id'],
            'success_status' => $record['context']['success_status'],
            'message'       => $record['message'],
            'created_at'    => date("Y-m-d H:i:s"),
        );       
        DB::connection()->table($this->table)->insert($data);     
    }
}