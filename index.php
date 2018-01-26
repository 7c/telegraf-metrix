<?php
class metrix {
    private $telegraf;
    private $scheme;
    private $host;
    private $lastHearthbeat;
    function __construct($telegraf="udp://127.0.0.1:8094") {
        $parsed = parse_url($telegraf);
        if (!$parsed) throw new Exception("Please define a valid target");
        if (!$parsed['scheme']) throw new Exception("Please define a scheme. (udp is allowed)");
        $this->scheme = $parsed['scheme'];
        $this->host = $parsed['host'];
        $this->port = array_key_exists('port',$parsed) ? $parsed['port'] : '8094';
        $this->telegraf = $telegraf;
        declare(ticks=1);
        $lastHearthbeat=time();
    }

    public static function time(callable $call){
        $started = microtime(true);
        $call();
        return microtime(true)-$started;
    }

    // interval in seconds
    public function pulseEnable($appname,$interval=15) {
        register_tick_function(array(&$this, 'hearthbeat'),$appname,$interval);
    }

    private function hearthbeat($appname,$interval) {
        if (time()-$this->lastHearthbeat>$interval)
        {
            $this->send('pulse',['appname'=>$appname],['lastSignal'=>time(),'heartbeat'=>1]);
            $this->lastHearthbeat=time();
        }
    }

    // The key is the measurement name and any optional tags separated by commas. 
    // Measurement names must escape commas and spaces. 
    // Tag keys and tag values must escape commas, spaces, and equal signs. Use a backslash (\) to escape characters, for example: \ and \,. All tag values are stored as strings and should not be surrounded in quotes.
    // Fields ////////////////////////
    // Fields are key-value metrics associated with the measurement. Every line must have at least one field. Multiple fields must be separated with commas and not spaces.
    // Field keys are always strings and follow the same syntactical rules as described above for tag keys and values. Field values can be one of four types. The first value written for a given field on a given measurement defines the type of that field for all series under that measurement.
    // Integers are numeric values that do not include a decimal and are followed by a trailing i when inserted (e.g. 1i, 345i, 2015i, -10i). Note that all values must have a trailing i. If they do not they will be written as floats.
    // Floats are numeric values that are not followed by a trailing i. (e.g. 1, 1.0, -3.14, 6.0e5, 10).
    // Boolean values indicate true or false. Valid boolean strings are (t, T, true, True, TRUE, f, F, false, False and FALSE).
    // Strings are text values. All string values must be surrounded in double-quotes ". If the string contains a double-quote, it must be escaped with a backslash, e.g. \".
    public function line(string $measurement,array $tags,$fields) {
        if (preg_match("/[^a-zA-Z0-9_, .]/",$measurement)>0) return false;
        if (is_array($fields)) foreach($fields as $k=>$v) if (preg_match("/[^a-zA-Z0-9_, .]/",$k)>0) return false;
        if (is_array($tags)) foreach($tags as $k=>$v)   if (preg_match("/[^a-zA-Z0-9_, .]/",$k)>0) return false;

        // escape measurement
        $measurement = preg_replace('/(,| )/',"\\\\".'${1}',$measurement);
        // var_dump($fields);
        // escape tags
        // var_dump($tags);
        $tagsPart="";
        
        // escape and compose tags
        // TODO: Tags should be sorted by key before being sent for best performance.
        if (is_array($tags)) 
            foreach($tags as $k=>$v)
            {
                $k = preg_replace('/(,| )/',"\\\\".'${1}',$k);
                $v = preg_replace('/(,| )/',"\\\\".'${1}',$v);
                $tagsPart.=",$k=$v";
            }
        // escape and compose fields
        $tmpFields=[];
        $fieldsPart="";
        if (is_array($fields))
            foreach($fields as $k=>$v)
            {
                // keys are escaped with same rules as tag keys
                $k = preg_replace('/(,| )/',"\\\\".'${1}',$k);

                // values of fields differ based on value type 
                if (is_bool($v)) $tmpFields[]="$k=".$v?'true':'false';
                else if (is_string($v)) {
                    $v=preg_replace("/\"/","\\\"",$v);
                    $tmpFields[]="$k=\"$v\"";
                } else if (is_integer($v)) {
                    $tmpFields[]="$k=$v"."i";
                } else if (is_float($v) || is_double($v)) {
                    $tmpFields[]="$k=$v";
                } else throw new Exception("Unknown field value by field $k");
            }
        else if (is_integer($fields) || is_float($fields) || is_double($fields)) $tmpFields[]="value=$fields";
        if (count($tmpFields)>0) $fieldsPart=" ".join(',',$tmpFields);

        $line = $measurement.$tagsPart.$fieldsPart;
        return $line;
    }
    public function send(string $measurement,array $tags,$fields) {
        $payload = $this->line($measurement,$tags,$fields);
        if ($payload) {
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            //cpu_load_short,host=server01,region=us-west value=1
            if ($this->scheme==='udp') {
                // echo ">$payload\n";
                // echo $this->host.":".$this->port."\n";
                socket_sendto($sock, $payload, strlen($payload), 0, $this->host, $this->port);
                socket_close($sock);
                return $payload;
            }
            throw new Exception("not supported scheme {$this->scheme}");
                
        }
    }
}

