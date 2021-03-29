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
$id   = (isset($queries['id'])   ? strtolower($queries['id'])   : null);
$sort = (isset($queries['sort']) ? strtolower($queries['sort']) : null);

//$id   = null;
//$sort = 'd';

$_idlist = json_decode(file_get_contents(VALID_COUNTERS));
$idlist  = array_map('strtolower', $_idlist->valid);

// did we get a counter ID?
if($id !== null) {
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
    $ix = 0;
    $out = '[';
    foreach($idlist as $id) {
        // build the log file name from the ID and "_count.log"
        //$counter = $id . '_count.json';
        // path + counter file
        $cntfile = LOG_FOLDER . $id . '_count.json';;
        if(file_exists($cntfile)) {
            $filecnt = fopen($cntfile,'r');
            // get 128 characters, it's unlikely that a counter
            // would get that big.
            $json = fgets($filecnt,128);
            fclose($filecnt);
            // add a counter to the array
            $out = $out . ($ix > 0 ? ',' : '[');
            $out = $out . '{"id":"'.$id.'","data":';
            $out = $out . $json . '}';
            // only used above to decide if we need a comma
            $ix += 1;
        }
    }
    $out = $out . ']';

    // is sorting selected?
    if($sort !== null) {
        $data = json_decode($out);

        // ascendind
        if($sort === 'a' || $sort === '') {
            usort($data, function($a, $b) {
                if ($a->data->count === $b->data->count) return 0;
                return (($a->data->count < $b->data->count)?-1:1);
            });
        }
        // descending
        if($sort === 'd') {
            usort($data, function($a, $b) {
                if ($a->data->count === $b->data->count) return 0;
                return (($a->data->count > $b->data->count)?-1:1);
            });
        }
        // done!
        $out = json_encode($data);
    }
    $result = $out;
}
header("HTTP/1.0 200 OK");
header("Content-Type: application/json; charset=utf-8");
header("Content-Encoding: text");
echo $result;
exit;
?>