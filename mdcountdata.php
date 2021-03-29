<?php
/*
    Usage:

        Return data for all counters -

        GET http[s]://your-server/path-to-file/mdcountdata.php
    
            OR
    
        Return data for a specific counter -

        GET http[s]://your-server/path-to-file/mdcountdata.php?id=testtest
    
    Report data returned:
        
        One or more counters - 

        [
            {
                "id": "sensornet",
                "data": {
                    "count": 30,
                    "time": 1616458038,
                    "dtime": [
                        "20210322",
                        "190718"
                    ]
                }
            }
        ]

*/
class logdata {
    public $id = '';
    public $count = 0;
    public $time = 0;
    public $dtime = array('19700101','000001');
}

define('VALID_COUNTERS', './counters.json');

function tzone() {
    $tmp = json_decode(file_get_contents('./tzone.json'));
    return $tmp->tz;
}

define('LOG_FOLDER', './logs/');

// MUST be done like this for PHP files that are 'linked'
$queries = array();

parse_str($_SERVER['QUERY_STRING'], $queries);

$_id = (isset($queries['id']) ? $queries['id'] : null);

// did we get a counter ID?
if($_id !== null) {
    $id = strtolower($_id);
    $_idlist = json_decode(file_get_contents(VALID_COUNTERS));
    $idlist = array_map('strtolower', $_idlist->valid);
    // is the counter ID known?
    if(in_array($id, $idlist)) {
        // build the log file name from the ID and "_count.log"
        $counter = $id . '_count.json';
        // path + counter file
        $cntfile = LOG_FOLDER . $counter;

        $data = new stdClass();
        $data->ldata = new logdata();
        if(file_exists($cntfile)) {
            $filecnt = fopen($cntfile,'r');
            // get 128 characters, it's unlikely that counter
            // would get that big.
            $json = fgets($filecnt,128);
            fclose($filecnt);
            //$result = '['.$json.']';
            $result = '[{"id":"'.$id.'","data":'.$json.'}]';
        } else {
            $result = '[{"error":true,"msg":"file ['.$cntfile.'] does not exist"}]';
        }
    } else {
        $result = '[{"error":true,"msg":"['.$id.'] not found in '.VALID_COUNTERS.'"}]';
    }
} else {
    $_idlist = json_decode(file_get_contents('./counters.json'));
    $idlist = array_map('strtolower', $_idlist->valid);

    $out = '';
    $ix = 0;
    foreach($idlist as $id) {
        // build the log file name from the ID and "_count.log"
        $counter = $id . '_count.json';
        // path + counter file
        $cntfile = LOG_FOLDER . $counter;
        if(file_exists($cntfile)) {
            $filecnt = fopen($cntfile,'r');
            // get 128 characters, it's unlikely that counter
            // would get that big.
            $json = fgets($filecnt,128);
            fclose($filecnt);

            $out = $out . ($ix > 0 ? ',' : '[');
            $out = $out . '{"id":"'.$id.'","data":';
            $out = $out . $json . '}';

            $ix += 1;
        }
    }
    $out = $out . ']';
    $result = $out;
}
header("HTTP/1.0 200 OK");
header("Content-Type: application/json; charset=utf-8");
header("Content-Encoding: text");
echo $result;
exit;
?>