<?php
/*

    See https://github.com/jxmot/markdown-hitcounter for detailed
    information not contained here.

    Usage:

        Return data for all counters -

        GET http[s]://your-server/path-to-file/mdcountdata.php
    
            OR
    
        Return data for a specific counter -

        GET http[s]://your-server/path-to-file/mdcountdata.php?id=testtest

            OR
    
        Return data for all counters, but sorted -

        GET http[s]://your-server/path-to-file/mdcountdata.php?[csort|tsort|isort]=[a|d]

            Where:
                csort - sort by count 
                tsort - sort by time of last count 
                isort - sort by counter ID 

                a - sort ascending 
                d - sort descending 


        Return sorted data, but limit the quantity of counters returned

        GET http[s]://your-server/path-to-file/mdcountdata.php?csort=a|[&limit=[1-n]]

            Where:
                limit - limit the quantity of counters to return
                1-n   - quantity

    
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
define('LOG_FOLDER', './logs/');

function tzone() {
    $tmp = json_decode(file_get_contents('./tzone.json'));
    return $tmp->tz;
}

function countsort($sort, $out, $limit = null) {
    $data = json_decode($out);
    // ascending
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
    if($limit !== null && ($limit > 0 && $limit < count($data))){
        $data = array_slice($data, 0, $limit);
    }
    return json_encode($data);
}

function timesort($sort, $out, $limit = null) {
    $data = json_decode($out);
    // ascending
    if($sort === 'a' || $sort === '') {
        usort($data, function($a, $b) {
            if ($a->data->time === $b->data->time) return 0;
            return (($a->data->time < $b->data->time)?-1:1);
        });
    }
    // descending
    if($sort === 'd') {
        usort($data, function($a, $b) {
            if ($a->data->time === $b->data->time) return 0;
            return (($a->data->time > $b->data->time)?-1:1);
        });
    }
    // done!
    if($limit !== null && ($limit > 0 && $limit < count($data))){
        $data = array_slice($data, 0, $limit);
    }
    return json_encode($data);
}

function idsort($sort, $out, $limit = null) {
    $data = json_decode($out);
    // ascending
    if($sort === 'a' || $sort === '') {
        usort($data, function($a, $b) {
            if ($a->id === $b->id) return 0;
            return (($a->id < $b->id)?-1:1);
        });
    }
    // descending
    if($sort === 'd') {
        usort($data, function($a, $b) {
            if ($a->id === $b->id) return 0;
            return (($a->id > $b->id)?-1:1);
        });
    }
    // done!
    if($limit !== null && ($limit > 0 && $limit < count($data))){
        $data = array_slice($data, 0, $limit);
    }
    return json_encode($data);
}

// MUST be done like this for PHP files that are 'linked'
$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);
// return a single counter by ID
$id    = (isset($queries['id'])    ? strtolower($queries['id'])    : null);
// return all counters, ordered by count
$csort = (isset($queries['csort']) ? strtolower($queries['csort']) : null);
// return all counters, ordered by time of last count
$tsort = (isset($queries['tsort']) ? strtolower($queries['tsort']) : null);
// return all counters, ordered by ID
$isort = (isset($queries['isort']) ? strtolower($queries['isort']) : null);
// for sorts, limit number of counters returned
$limit = (isset($queries['limit']) ? $queries['limit'] : null);

//$id    = null;
//$csort = 'd';
//$tsort = null;
//$isort = null;
//$limit = 1;

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
            $out = $out . ($ix > 0 ? ',' : '');
            $out = $out . '{"id":"'.$id.'","data":';
            $out = $out . $json . '}';
            // only used above to decide if we need a comma
            $ix += 1;
        }
    }
    $out = $out . ']';

    // is sorting selected?
    if($csort !== null) {
        // by count
        $out = countsort($csort, $out, $limit);
    }

    if($tsort !== null) {
        // by time
        $out = timesort($tsort, $out, $limit);
    }

    if($isort !== null) {
        // by ID
        $out = idsort($isort, $out, $limit);
    }

    $result = $out;
}
header("HTTP/1.0 200 OK");
header("Content-Type: application/json; charset=utf-8");
header("Content-Encoding: text");
echo $result;
exit;
?>