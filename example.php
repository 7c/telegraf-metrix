<?php
require_once "index.php";

$m = new metrix("udp://127.0.0.1");

while(true) {

    $m->send('Random Generator',
            ['type'=>'random'],
            ['measurement 1 to 100'=>rand(1,100),'measurement 1 to 500'=>rand(1,500)]
    );

    $m->send('Memory',
            ['from'=>'php'],
            ['usage'=>memory_get_usage(),'peak'=>memory_get_peak_usage()]
    );

    $m->send('Mixed',
            ['from'=>'key.subkey.lastkey'],
            time()
    );


    sleep(1);
}