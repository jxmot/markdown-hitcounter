<?php
// Usage:
//      <img src="http[s]://your-server/path-to-file/mdcount.php?id=testtest">
//
//      Use the "?id=testtest" query for testing, then edit counters.json 
//      and add IDs. They can be most any string(within reason) and are
//      case insensitive. 
//
require_once './php/timezone.php';
require_once './php/rightnow.php';

if(!file_exists('./logs')) {
    mkdir('./logs', 0777, true);
}
define('LOG_FOLDER', './logs/');

// NOTE: You must create a folder called "logs" in the same folder where 
// you have placed this file.
//
// MUST be done like this for PHP files that are 'linked'
$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);
$_id = (isset($queries['id']) ? $queries['id'] : null);

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
        "log": {
            "count": 1,
            "time": 1616952000,
            dtime: ["20210328","122000"]
        }
    }

*/
class logdata {
    public $count = 0;
    public $time = 0;
    public $dtime = array('19700101','000001');
}

if(isset($_id)) {
    $id = strtolower($_id);
    $_idlist = json_decode(file_get_contents('./counters.json'));
    $idlist = array_map('strtolower', $_idlist->valid);
    if(in_array($id, $idlist)) {
        // build the log file name from the ID and "_count.log"
        $counter = $id . '_count.json';
        // if testing put the "testtest" log elsewhere
        if($id === 'testtest') $cntpath = './';
        else $cntpath = LOG_FOLDER;
        // path + file
        $cntfile = $cntpath . $counter;
        
        $data = new stdClass();
        $data->ldata = new logdata();
        
        // if the counter file doesn't exist then create 
        // it and set it to 1, write the file and close it
        if(!file_exists($cntfile)) {
            $filecnt = fopen($cntfile,'w');
            // initialize the counter file
            $data->ldata->count = 1;
            $data->ldata->time = time();
            $data->ldata->dtime = rightnow('arr');
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
            $data->ldata = json_decode($json);
            // update the data...
            $data->ldata->count = $data->ldata->count + 1;
            $data->ldata->time = time();
            $data->ldata->dtime = rightnow('arr');
            // opens a file to contain the new hit number
            $filecnt = fopen($cntfile,'w');
        }
        fwrite($filecnt, json_encode($data->ldata));
        fflush($filecnt);
        fclose($filecnt);

        // if testing use an image that is easily seen
        $imgfile = ($id === 'testtest' ? $testimg : $countimg);
    } else $imgfile = $errimg;
} else $imgfile = $oopsimg;

$imgcontent = file_get_contents($imgfile);

// all good, all of the time
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