<?php

use PHPUnit\Framework\TestCase;
use Devium\Processes\Processes;
use Symfony\Component\Process\Process;

class ProcessesTest extends TestCase
{
    public function testProcessesOnUnix()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test run only on Unix');
        }

        $process = Process::fromShellCommandline('tests/assets/while');

        $process->start();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        while ($process->isRunning()) {
            $this->assertGreaterThan(0, $process->getPid());

            $processes = new Processes(true);
            var_dump($process->getPid());
            $this->assertTrue($processes->exists($process->getPid()));

            $p = $processes->get()[$process->getPid()];

            $this->assertArrayHasKey('pid', $p);
            $this->assertIsInt($p['pid']);
            $this->assertArrayHasKey('ppid', $p);
            $this->assertIsInt($p['ppid']);
            $this->assertArrayHasKey('name', $p);
            $this->assertIsString($p['name']);

            $this->assertArrayHasKey('uid', $p);
            $this->assertIsInt($p['uid']);
            $this->assertArrayHasKey('cpu', $p);
            $this->assertIsFloat($p['cpu']);
            $this->assertArrayHasKey('memory', $p);
            $this->assertIsFloat($p['memory']);
            $this->assertArrayHasKey('cmd', $p);
            $this->assertIsString($p['cmd']);

            $process->stop(0, 9);
        }

        $this->assertNull($process->getPid());
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isTerminated());
    }

    public function testProcessesOnWindows()
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test run only on Windows');
        }

        $process = Process::fromShellCommandline('tests/assets/while.exe');

        $process->start();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        while ($process->isRunning()) {
            $this->assertGreaterThan(0, $process->getPid());

            $processes = new Processes(true);
            $this->assertTrue($processes->exists($process->getPid()));

            $p = $processes->get()[$process->getPid()];

            $this->assertArrayHasKey('pid', $p);
            $this->assertIsInt($p['pid']);
            $this->assertArrayHasKey('ppid', $p);
            $this->assertIsInt($p['ppid']);
            $this->assertArrayHasKey('name', $p);
            $this->assertIsString($p['name']);

            $process->stop(0, 9);
        }

        $this->assertNull($process->getPid());
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isTerminated());
    }
}
