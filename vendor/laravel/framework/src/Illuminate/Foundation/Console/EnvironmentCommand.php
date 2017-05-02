<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class EnvironmentCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'env';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Display the current framework environment';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //将字符串作为标准输出写入
        $this->line('<info>Current application environment:</info> <comment>'.$this->laravel['env'].'</comment>');
    }
}
