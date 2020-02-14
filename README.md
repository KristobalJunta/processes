# Processes package [![Build Status](https://travis-ci.org/webdevium/processes.svg?branch=master)](https://travis-ci.org/webdevium/processes)

## Usage

```php
use use Devium\Processes\Processes;

// some PID
$pid = 1234;

$processes = new Processes(true);
$processes->get() // return array of processes where key is PID
$processes->exists($pid) // true or false
```

## Structure of processes array

#### For windows
```json
{
  "PID": {
    "pid": "integer",
    "ppid": "integer",
    "name": "string"
  }
}
```

#### For POSIX
```json
{
  "PID": {
    "pid": "integer",
    "ppid": "integer",
    "name": "string",
    "uid": "integer",
    "cpu": "float",
    "memory": "float",
    "cmd": "string"
  }
}
```

## Testing
```sh
composer test
```

## License

The Processes package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
