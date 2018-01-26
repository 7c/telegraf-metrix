<?php
require_once "index.php";

$pulse = new metrix("udp://127.0.0.1:9255");
$pulse->pulseEnable('example.php');
$metrix = new metrix("udp://127.0.0.1");

while(true) {
    print "Tick\n";
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

    $metrix->send('Mixed',
            [],
            ['fetch_timing'=>metrix::time(function() { usleep(300); })]
    );

    sleep(1);
    
}