<?php

use Symfony\Component\Process\Process;

$process = new Process(['php', 'while.php']);

$process->start();

$this->assertTrue($process->isStarted());
$this->assertFalse($process->isTerminated());

$this->assertGreaterThan(0, $process->getPid());

$processes = new Processes(true);
$this->assertTrue($processes->exists($process->getPid()));
