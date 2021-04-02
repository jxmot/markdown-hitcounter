<?php
/*
    Usage:
        <img src="http[s]://your-server/path-to-file/mdcount.php?id=testtest">
    
        Use the "?id=testtest" query for testing, then edit counters.json 
        and add IDs. They can be most any string(within reason) and are
        case insensitive. 
    
*/

// get our configured time zone
function tzone() {
    $tmp = json_decode(file_get_contents('./tzone.json'));
    return $tmp->tz;
}

// create the log output folder if it does not exist
if(!file_exists('./logs')) {
    mkdir('./logs', 0777, true);
}
define('LOG_FOLDER', './logs/');

// MUST be done like this for PHP files that are 'linked'
// like this - <img src="http://[your-server]/[some-folder]/mdcount.php?id=example_1">
$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);
$_id = (isset($queries['id']) ? $queries['id'] : null);

// read the image map and assemble the path+file
// for each image.
$imgs = json_decode(file_get_contents('./images.json'));
// image files, replace with your own if you like
$testimg  = $imgs->path . $imgs->testimg;     // for testing, use ?id=testtest
$countimg = $imgs->path . $imgs->countimg;    // normal use, ?id=[must be in counters.json]
// some rather obvious error message images, they're 1500x1500
$errimg   = $imgs->path . $imgs->errimg;      // id was not found in the list
$oopsimg  = $imgs->path . $imgs->oopsimg;     // "?id=..." is missing, no query found

$imgfile  = null;

/*
    Counter file contents:

    {
        "count": 2,
        "time": 1616961746,
        "dtime": [
            "2021-04-02",
            "01:07:16"
        ]
    }
*/
class logdata {
    public $count = 0;
    public $time = 0;
    public $dtime = array('1970-01-01','00:00:01');
}

// did we get a counter ID?
if(isset($_id)) {
    $id = strtolower($_id);
    $_idlist = json_decode(file_get_contents('./counters.json'));
    $idlist = array_map('strtolower', $_idlist->valid);
    // is the counter ID known?
    if(in_array($id, $idlist)) {
        // build the log file name from the ID and "_count.log"
        $counter = $id . '_count.json';
        // if testing put the "testtest" log elsewhere
        if($id === 'testtest') $cntpath = './';
        else $cntpath = LOG_FOLDER;
        // path + counter file
        $cntfile = $cntpath . $counter;
        
        $data = new logdata();
        
        // if the counter file doesn't exist then create 
        // it and set it to 1, write the file and close it
        if(!file_exists($cntfile)) {
            $filecnt = fopen($cntfile,'w');
            // initialize the counter file
            $data->count = 1;
        } else {
            // the file exists, open it, read it, close it,
            // increment the count, open it again, write it, 
            // and finally close it
            $filecnt = fopen($cntfile,'r');
            // get 128 characters, it's unlikely that counter
            // would get that big.
            $json   = fgets($filecnt,128);
            fclose($filecnt);
            // JSON -> object
            $data = json_decode($json);
            // update the data...
            $data->count = intval($data->count + 1);
            // opens a file to contain the new hit number
            $filecnt = fopen($cntfile,'w');
        }
        // fill in the new count date and time...
        $tm = $data->time = time();
        $dt = new DateTime("@$tm");
        $tz = new DateTimeZone(tzone());
        $dt->setTimezone($tz);
        $data->dtime = array($dt->format('Y-m-d'), $dt->format('H:i:s'));
        unset($tz);
        unset($dt);
        // save, flush and close the file
        fwrite($filecnt, json_encode($data));
        fflush($filecnt);
        fclose($filecnt);
        // if testing use an image that is easily seen
        $imgfile = ($id === 'testtest' ? $testimg : $countimg);
    } else $imgfile = $errimg;
} else $imgfile = $oopsimg;

$imgcontent = file_get_contents($imgfile);

// all good, all of the time, an image will always be returned
header('HTTP/1.0 200 OK');
// hopefully the image won't be cached so that the counter is correct
header('Expires: Thu, 1 Jul 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); 
header('Cache-Control: no-store, no-cache, must-revalidate'); 
header('Cache-Control: post-check=0, pre-check=0', false); 
header('Pragma: no-cache'); 
// necessary stuff...
header('Content-type: image/png');
header('Content-Length: '.filesize($imgfile));
// not really necessary, but depending what you're using
// for debugging this info will contain image name and size
header('Image-NameSize: '.$imgfile.'  '.filesize($imgfile));
// send it!
echo $imgcontent;
?>