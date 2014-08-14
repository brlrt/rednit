<?php
namespace TinderFun\Console;

use Symfony\Component\Console\Application as BaseApplication;
use TinderFun\Command\BotCommand;

class Application extends BaseApplication
{
    private static $logo = <<<EOT

                    .I,
                     I77.
                     777777
             .     .77777777.
           .77    .77777777777
          .7777  =777777777777.
        .7777777777777777777777
        777777777777777777777777
       $$$$$$$$$$$$$$$$$$$$$$$$$.
       $$$$$$$$$$$$$$$$$$$$$$$$$+
       $$$$$$$$$$$$$$$$$$$$$$$$$
       .$$$$$$$$$$$$$$$$$$$$$$$
        .$$$$$$$$$$$$$$$$$$$$$
          .$$$$$$$$$$$$$$$$$.
             '""$$$$$$$""'


EOT;

    public function __construct()
    {
        parent::__construct();

        $this->setName("TinderFun");
        $this->setVersion("0.1");

        $this->registerCommands();
    }

    public function getHelp()
    {
        return self::$logo . parent::getHelp();
    }

    protected function registerCommands()
    {
        $this->addCommands([
            new BotCommand(),
        ]);
    }
} 
