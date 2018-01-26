# Telegraf Metrix

Supports telegraf line protocol and sends metrics over udp (for now). Line protocol uses nano second precisious timestamp, we will let this stamping telegraf for now

I preper UDP protocol because php is stateless and not able to run async functions natively. I do not want to block my operations with TCP which has a handshake and many checks.

Planning to support TCP by next versions

## Install

```composer require taskinosman/telegraf-metrix @dev```

## Telegraf Configuration
Enable [[inputs.socket_listener]] inside telegraf.conf with a service address like udp://:8094 to enable UDP from 0.0.0.0. You may also choose to activate UDP for 127.0.0.1 which is recommended way

## Usage
- default target is udp://127.0.0.1:8094
- target either has to be defined with all its components scheme://host:port or kept default
- measurement name: ^[a-zA-Z0-9_, .]+$
- tag keys, tag values: ^[a-zA-Z0-9_,. =]+$
- tags are optional
- fields are optional
- send() returns line protocol formatted or false by error
- time will be stamped from telegraf (for now)


```
$metrix = new metrix("udp://127.0.0.1");

while(true) {

    $metrix->send('Random Generator',
            ['type'=>'random'],
            ['measurement 1 to 100'=>rand(1,100),'measurement 1 to 500'=>rand(1,500)]
    );

    $metrix->send('Memory',
            ['from'=>'php'],
            ['usage'=>memory_get_usage(),'peak'=>memory_get_peak_usage()]
    );

    $metrix->send('Mixed',
            ['from'=>'key.subkey.lastkey'],
            time()
    );

    sleep(1);
}
```
## Pulse Feature (alpha)
Basic pulse/bearthbeat function included. This function will send hearthbeats from the application with with given $appname as identifier to telegraf using udp. These pulses can be used to detect anomalies or down times of your application.

Given example will send a pulse/hearthbeat to telegraf every 15 seconds automatically
```
$metrix = new metrix("udp://127.0.0.1");
$metrix->pulseEnable('example.php',15);
```


## Line protocol details
https://docs.influxdata.com/influxdb/v0.9/write_protocols/line/

[key] [tags] [fields] [timestamp]

```
Fields are key-value metrics associated with the measurement. Every line must have at least one field. Multiple fields must be separated with commas and not spaces.

Field keys are always strings and follow the same syntactical rules as described above for tag keys and values. Field values can be one of four types. The first value written for a given field on a given measurement defines the type of that field for all series under that measurement.

Integers are numeric values that do not include a decimal and are followed by a trailing i when inserted (e.g. 1i, 345i, 2015i, -10i). Note that all values must have a trailing i. If they do not they will be written as floats.

Floats are numeric values that are not followed by a trailing i. (e.g. 1, 1.0, -3.14, 6.0e5, 10).

Boolean values indicate true or false. Valid boolean strings are (t, T, true, True, TRUE, f, F, false, False and FALSE).

Strings are text values. All string values must be surrounded in double-quotes ". If the string contains a double-quote, it must be escaped with a backslash, e.g. \".
```