<?php

declare(strict_types=1);

namespace luckCode\data\save\task;

use luckCode\data\save\engines\IFileSaveEngine;
use luckCode\data\save\manager\DataSaveWorker;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class DataSaveTaskAsync extends AsyncTask
{

    /** @var string $filePath */
    private $filePath;

    /** @var array $contents */
    private $contents;

    /** @var string $writeEngine */
    private $writeEngine;

    /**
     * DataSaveTaskAsync constructor.
     * @param string $filePath
     * @param array $contents
     * @param string $writeEngine
     */
    public function __construct(string $filePath, array $contents, string $writeEngine)
    {
        $this->filePath = $filePath;
        $this->contents = $contents;
        $this->writeEngine = $writeEngine;
    }

    /**
     * @inheritDoc
     */
    public function onRun()
    {
        /** @var IFileSaveEngine $engineWrite */
        $engineWrite = new $this->writeEngine();
        $this->setResult($engineWrite->save($this->filePath, $this->contents), false);
    }

    /** @param Server $server */
    public function onCompletion(Server $server)
    {
        if($this->getResult()) {
            DataSaveWorker::$successWorked[] = $this->filePath;
        }
    }
}