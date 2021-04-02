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

// check for debug/test mode
if(!defined('_DEBUG') || _DEBUG === false) {

    $mdcountdata = (isset($queries['mdcdata']) ? strtolower($queries['mdcdata']) : THISSRVR.'/mdcountdata.php');

    // MUST be done like this for PHP files that are 'linked'
    $queries = array();
    if(isset(QRYSTR)) {
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
    if(isset(QRYSTR)) {
        $q = QRYSTR;
        echo "<p>$q</p>\n";
    }

    $mdcountdata = THISSRVR.'/mdcountdata.php';

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
$linkmsg = 'Open in New Tab or Window';

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
<link rel="stylesheet" href="./mdreport.css?=_<?php echo time(); ?>">
<div class="table-responsive table-container">
    <table id="hit-table" class="table table-sm">
        <thead>
<?php
    for($ix = 0; $ix < count($thitems); $ix++) {
        if($ix !== $sortidx) {   
            echo '            <th'.($ix !== 0 ? ' id="hit-table-col'.$ix.'" class="orderhover" data-ix="'.$ix.'"' : '').'>'.$thitems[$ix].'</th>'."\n";
        } else {
            echo '            <th id="hit-table-col'.$ix.'" class="orderhover" data-order="'.$sortdir.'" data-ix="'.$ix.'">'.$thitems[$ix].'<span id="hit-table-order'.$ix.'" data-order="'.$sortdir.'" data-ix="'.$ix.'" class="'.$dircss.'">&nbsp;</span></th>'."\n";
        }
    }
?>
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

    $repqty = count($counters);
    $tablecaption = "Top $repqty repositories. Sorted by $sorttitle[0] in $sorttitle[1] order";

    for($ix = 0; $ix < $repqty; $ix++) {
        echo "        <tr>\n";
        echo '            <th scope="row">'.($ix+1).'</th>'."\n";
        echo "            <td>".$counters[$ix]->data->count."</td>\n";
        echo '            <td><a target="_blank" href="'.$repohome.$counters[$ix]->id.'" title="'.$linkmsg.'">'.$counters[$ix]->id."</a></td>\n";
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
<script>
    tippy('.orderhover', {
        theme: 'light',
        content: 'Click to select or change sorting order.',
    });
</script>
<?php
} else {    // if(file_exists($thfile))
    echo "<h1>Header File was not found - $thfile</h1>\n";
}
?>