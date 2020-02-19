<?php

namespace Devium\Processes;

use Symfony\Component\Process\Process;
use Throwable;
use function count;
use const DIRECTORY_SEPARATOR;

class Processes
{

    const COLUMNS = ['pid', 'ppid', 'uid', '%cpu', '%mem', 'comm', 'args'];
    const REGEX = '/^[\s]?(\d+)[\s]+(\d+)[\s]+(\d+)[\s]+(\d+\.\d+)[\s]+(\d+\.\d+)[\s]+([\S]+(?:\s+<defunct>)?)[\s]+(.*)/';
    const PID = 'pid';

    private $processes = [];

    /**
     * @param bool $all
     */
    public function __construct(bool $all = false)
    {
        $this->scan($all);
    }

    /**
     * @param bool $all
     * @return Processes
     */
    public function scan(bool $all = false): Processes
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->windows();
        } else {
            $this->unix($all);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->processes;
    }

    /**
     * @param int $pid
     * @return bool
     */
    public function exists(int $pid): bool
    {
        return array_key_exists($pid, $this->processes);
    }

    /**
     * @return array
     */
    private function windows(): array
    {

        $processes = [];

        /**
         * Fastlist source code
         * @link https://github.com/MarkTiedemann/fastlist
         */
        $process = new Process([__DIR__ . '/../fastlist.exe']);
        $process->run();
        $output = $process->getOutput();
        $output = explode("\n", trim($output));
        $output = array_map(static function ($line) {
            return explode("\t", $line);
        }, $output);
        array_map(static function ($item) use (&$processes) {
            // var_dump($item);
            list($name, $pid, $ppid) = $item;
            $processes[(int)$pid] = [
                static::PID => (int)$pid,
                'ppid' => (int)$ppid,
                'name' => $name,
            ];
        }, $output);

        $this->processes = $processes;
        return $this->processes;
    }

    /**
     * @param bool $all
     * @return string
     */
    protected function getFlags(bool $all = false): string
    {
        return ($all ? 'a' : '') . 'wwxo';
    }

    /**
     * @param bool $all
     * @return array
     */
    private function unix(bool $all = false)
    {
        try {
            try {
                return $this->unixOneCall($all);
            } catch (Throwable $e) {
                return $this->unixMultiCall($all);
            }
        } catch (Throwable $e) {
            return $this->processes;
        }
    }

    /**
     * @param bool $all
     * @return array
     */
    private function unixOneCall($all = false): array
    {
        $processes = [];

        $process = new Process(['ps', $this->getFlags($all), implode(',', static::COLUMNS)]);
        $process->run();

        $output = $process->getOutput();
        $output = explode("\n", $output);
        array_shift($output);

        foreach ($output as $line) {
            preg_match(static::REGEX, $line, $matches);
            if (count(static::COLUMNS) !== count($matches) - 1) {
                continue;
            }
            if (!isset($matches[1])) {
                continue;
            }
            try {
                $pid = (int)$matches[1];
                $processes[$pid] = [
                    static::PID => $pid,
                    'ppid' => (int)$matches[2],
                    'uid' => (int)$matches[3],
                    'cpu' => (float)$matches[4],
                    'memory' => (float)$matches[5],
                    'name' => $matches[6],
                    'cmd' => $matches[7],
                ];
            } catch (Throwable $e) {

            }
        }

        $this->processes = $processes;
        return $this->processes;
    }

    /**
     * @param bool $all
     * @return array
     */
    private function unixMultiCall($all = false): array
    {
        $processes = [];

        foreach (static::COLUMNS as $cmd) {
            if (static::PID === $cmd) {
                continue;
            }
            $process = new Process(['ps', $this->getFlags($all), static::PID . ",${cmd}"]);
            $process->run();

            $output = $process->getOutput();
            $output = explode("\n", $output);
            array_shift($output);

            foreach ($output as $line) {
                $line = trim($line);
                $split = array_filter(preg_split('/\s+/', $line));
                $pid = $split[0];
                if (!isset($split[1])) {
                    continue;
                }
                $val = trim($split[1]);

                if (!isset($processes[$pid])) {
                    $processes[$pid] = [
                        static::PID => $pid,
                    ];
                }
                if (in_array($cmd, ['%cpu', '%mem'], true)) {
                    $processes[$pid][$cmd] = (float)$val;
                } else if (in_array($cmd, ['ppid', 'uid'], true)) {
                    $processes[$pid][$cmd] = (int)$val;
                } else {
                    $processes[$pid][$cmd] = $val;
                }
            }
        }

        $this->processes = array_filter($processes, static function ($item) {
            $filled = true;
            foreach (static::COLUMNS as $cmd) {
                if (empty($item[$cmd])) {
                    $filled = false;
                }
            }
            return $filled;
        });
        return $this->processes;
    }
}
