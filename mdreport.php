<?php
// if false run normally, if true then fake the query
define('_DEBUG', false);

// renders and echoes the defines inside of a <p>
define('_DEBUGDEF', false);
require_once './stddefines.php';

// get the configured time zone
function tzone() {
    $tmp = json_decode(file_get_contents('./tzone.json'));
    return $tmp->tz;
}

//$mdcountdata = '../../extcounter/mdcountdata.php';
$mdcountdata = THISSRVR.'/mdcountdata.php';

// check for debug/test mode
if(!defined('_DEBUG') || _DEBUG === false) {

//    $mdcountdata = THISSRVR.'/mdcountdata.php';

    // MUST be done like this for PHP files that are 'linked'
    $queries = array();
    if(QRYSTR !== null) {
        parse_str(QRYSTR, $queries);
    }
    // return all counters, ordered by count
    $csort = (isset($queries['csort']) ? strtolower($queries['csort']) : null);
    // return all counters, ordered by time of last count
    $tsort = (isset($queries['tsort']) ? strtolower($queries['tsort']) : null);
    // return all counters, ordered by ID
    $isort = (isset($queries['isort']) ? strtolower($queries['isort']) : null);
    // for sorts, limit number of counters returned
    $limit = (isset($queries['limit']) ? $queries['limit'] : null);
} else {
    // for testing the query string while _DEBUG is true
    if(QRYSTR !== null) {
        $q = QRYSTR;
        echo "<p>$q</p>\n";
    }

//    $mdcountdata = THISSRVR.'/mdcountdata.php';

    // set as needed for testing
    $csort = 'd';
    $tsort = null;
    $isort = null;
    $limit = 2;
}

$thfile = './mdreport-th.txt';
$arrup = 'sort-arrow-up';
$arrdn = 'sort-arrow-dn';
$repohome = 'https://github.com/jxmot/';
$linktitle = 'Open Link in New Tab or Window';
$thtitle = 'Click to select or to change the sorting order.';

if(file_exists($thfile)) {
    $fileid = fopen($thfile,'r');
    $thdata = fread($fileid,128);
    fclose($fileid);
    $thitems = explode(',', $thdata);

    $sortidx = ($csort !== null ? 1 : ($isort !== null ? 2 : ($tsort !== null ? 3 : -1)));
    $sortdir = ($csort !== null ? $csort : ($isort !== null ? $isort : ($tsort !== null ? $tsort : 'a')));

    $sorttitle = array(
                    ($sortidx === -1 ? '<h1>BAD Sort Choice!</h1>' : "<i>$thitems[$sortidx]</i>"),
                    ($sortdir === 'a' ? '<i>Ascending</i>' : ($sortdir === 'd' ? '<i>Descending</i>' : '<h1>BAD Sort Direction!</h1>'))
                );

    $dircss = ($sortdir === 'a' ? $arrup : ($sortdir === 'd' ? $arrdn : ''))
?>
<style>
<?php
echo file_get_contents('./mdreport.css');
?>
</style>
<div class="table-responsive table-container">
    <p style="text-align:center!important;">
        <br>
        <strong>
            This is a demonstration of the code found in 
            <br>
            <a href="https://github.com/jxmot/markdown-hitcounter" target="_blank" title="Open in new tab">markdown-hitcounter</a>.
        </strong>
    </p>
    <table id="hit-table" class="table table-sm hit-table">
        <thead>
            <tr>
<?php
    for($ix = 0; $ix < count($thitems); $ix++) {
        if($ix !== $sortidx) {   
            echo '            <th'.($ix !== 0 ? ' id="hit-table-col'.$ix.'" title="'.$thtitle.'" class="orderhover" data-ix="'.$ix.'"' : '').'>'.$thitems[$ix].'</th>'."\n";
        } else {
            echo '            <th id="hit-table-col'.$ix.'" title="'.$thtitle.'" class="orderhover" data-order="'.$sortdir.'" data-ix="'.$ix.'">'.$thitems[$ix].'<span id="hit-table-order'.$ix.'" data-order="'.$sortdir.'" data-ix="'.$ix.'" class="'.$dircss.'">&nbsp;</span></th>'."\n";
        }
    }
?>
            </tr>
        </thead>
        <tbody>
<?php
    // get(rebuild if in _DEBUG mode) the query string...
    $qry = null;
    if(!defined('_DEBUG') || _DEBUG === false) {
        $qry = '?' . QRYSTR;
    } else {
        $qry = '?' . ($csort !== null ? 'csort=' : ($isort !== null ? 'isort=' : ($tsort !== null ? 'tsort=' : 'csort='))) . $sortdir;
        if($limit !== null) {
            $qry = $qry . '&limit=' . $limit;
        }
    }

    // Create a stream
    $opts = array(
        'http'=>array(
            'method'=>'GET',
            'header'=>"Accept-language: en\r\n"
                    ."user-agent: custom\r\n"
                    ."Content-Type: application/json; charset=utf-8\r\n"
                    ."Content-Encoding: text\r\n"
        )
    );
    $context = stream_context_create($opts);
    
    $url = $mdcountdata . ($qry !== null ? $qry : '');
    $data = file_get_contents($url, false, $context);
    $counters = json_decode($data);

    if(gettype($counters) === 'array') {
        // create the table caption
        $repqty = count($counters);
        $tablecaption = "There are $repqty counters shown.<br>Sorted by $sorttitle[0] in $sorttitle[1] order";
    } else {
        // on rare occasion the hosting server I use has 
        // problems with file_get_contents() and a context 
        // where it never returns and times out.
        $tablecaption = "Server ERROR - [$url] returned = [$counters]";
    }

    for($ix = 0; $ix < $repqty; $ix++) {
        echo "        <tr>\n";
        // Rank value column - change between incrementing and 
        // decrementing based on column heading clicks. But if 
        // sorting is by "Repository"($isort) then the rank count 
        // is opposite, then it will match the alphabetical 
        // ordering.
        $tidx = ($isort === null ? ($sortdir === 'd' ? ($ix + 1) : ($repqty - $ix)) : ($sortdir === 'd' ? ($repqty - $ix) : ($ix + 1)));
        echo '            <th scope="row">'.$tidx.'</th>'."\n";
        echo "            <td>".$counters[$ix]->data->count."</td>\n";
        echo '            <td class="table-cell-ellipsis"><a target="_blank" href="'.$repohome.$counters[$ix]->id.'" title="'.$linktitle.'">'.$counters[$ix]->id."</a></td>\n";
        // NOTE: This block of code in the top of the if() isn't meant 
        // to be permanent. As mdcount.php was being developed the format 
        // of dtime[0] and dtime[1] changed. Originally the formats were
        // YYYYMMDD(Ymd) and HHMMSS(His), now they're formatted with 
        // separators. So this is here to "fix" the older counter data 
        // by inserting the separators here.
        if( (strpos($counters[$ix]->data->dtime[0],'/') === false) &&
            (strpos($counters[$ix]->data->dtime[0],'-') === false) && 
            (strpos($counters[$ix]->data->dtime[0],'.') === false) && 
            (strpos($counters[$ix]->data->dtime[1],':') === false) ) {
            // insert the separators...
            // YYYYMMDD -> YYYYMM-DD
            $newd = substr_replace($counters[$ix]->data->dtime[0], 
                                   '-', 
                                   strlen($counters[$ix]->data->dtime[0]) - 2, 0 );
            // YYYYMM-DD -> YYYY-MM-DD
            $newd = substr_replace($newd, 
                                   '-', 
                                   strlen($newd) - 5, 0 );
            // HHMMSS -> HHMM:SS
            $newt = substr_replace($counters[$ix]->data->dtime[1], 
                                   ':', 
                                   strlen($counters[$ix]->data->dtime[1]) - 2, 0 );
            // HHMM:SS -> HH:MM:SS
            $newt = substr_replace($newt, 
                                   ':', 
                                   strlen($newt) - 5, 0 );
            // done!
            $date = $newd . '<br>' . $newt;
        } else {
            // already formatted
            $date = $counters[$ix]->data->dtime[0] . '<br>' . $counters[$ix]->data->dtime[1];
        }

        echo "            <td>".$date."</td>\n";
        echo "        </tr>\n";
    }
?>
            <!-- BS4 likes to render this on the bottom of the table, but we have CSS that
                moves it to the top. 
            -->
            <caption><?php echo $tablecaption; ?></caption>
        </tbody>
    </table>
</div>
<?php
} else {    // if(file_exists($thfile))
    echo "<h1>Header File was not found - $thfile</h1>\n";
}
?>