<?php
/*
 * LOAD Indeed API
*/


defined('BASEPATH') OR exit('No direct script access allowed');

class Loadindeed {

    private $apiID;
    private $xmlData; // consist xml data
    public $loopJobData; // loop xml data to html
    public $totalresults; // will get val from xmldata
    public $start; // will get val from xmldata
    public $end; // will get val from xmldata
    public $query;
    var $params;
    
    public function __construct($params) {
        $this->query = $params['categoryname'];
    }
    
    public function loadJobs() {

        $CI =& get_instance();

        
        $CI->load->helper('common');
        $CI->load->library('browserdetect');

        $indeedUrl = 'http://api.indeed.com/ads/apisearch?';
        $qstring = null;


        $browser = new Browserdetect();
        $myBrowser = $browser->getName(). ', version ' .$browser->getVersion();
        $this->apiID = null; // your indeed api ID
        
        $format = (isset($_GET['format'])) ? urlencode($_GET['format']) : null;
        
        $this->query = urlencode($this->query);
        
        if($this->query != null)
            $query = $this->query;
        else
            $query = (isset($_GET['q']) && $_GET['q'] != null) ? urlencode($_GET['q']) : 'jobs';

        $location = (isset($_GET['l'])) ? urlencode($_GET['l']) : null;

        $sort = (isset($_GET['sort'])) ? urlencode($_GET['sort']) : 'relevance';
        $radius = (isset($_GET['radius'])) ? urlencode($_GET['radius']) : 25;
        $st = (isset($_GET['st'])) ? urlencode($_GET['st']) : null;
        $jt = (isset($_GET['jt'])) ? urlencode($_GET['jt']) : null;
        $start = (isset($_GET['start'])) ? urlencode($_GET['start']) : 0;
        $limit = (isset($_GET['limit'])) ? urlencode($_GET['limit']) : 20;
        $fromage = (isset($_GET['fromage'])) ? urlencode($_GET['fromage']) : null;

        $highlight = (isset($_GET['highlight'])) ? urlencode($_GET['highlight']) : 0;
        $filter = (isset($_GET['filter'])) ? urlencode($_GET['filter']) : 1;
        $latlong = (isset($_GET['latlong'])) ? urlencode($_GET['latlong']) : 0;
        $co = (isset($_GET['co'])) ? urlencode($_GET['co']) : $_SESSION['countrycode'];

        $chnl = null; // your channel
        $userip = (isset($_GET['userip'])) ? urlencode($_GET['userip']) : getIP();
        $useragent = (isset($_GET['useragent'])) ? urlencode($_GET['useragent']) : $myBrowser;
        
        $params = [
                'publisher' => $this->apiID,
                'q' => $query,
                'l' => $location,
                'v' => 2,
                'format' => $format,
                'sort' => $sort, // relevance or date
                'radius' => $radius, // search loc distance
                'st' => $st, // site type jobsite or employer
                'jt' => $jt, // "fulltime", "parttime", "contract", "internship", "temporary"
                'start' => $start, // start result
                'limit' => $limit, // max num results
                'fromage' => $fromage, // num of days backsearch
                'highlight' => $highlight, // set 1 will bold terms in snippet
                'filter' => $filter, // filter duplicate results 0 turns off
                'latlong' => $latlong, // if 1, returns lat and longtitude information
                'co' => $co, // search by country nz, us, ph
                'chnl' => $chnl, // channel name
                'userip' => $userip, // the ip number REQUIRED
                'useragent' => $useragent // browser REQUIRED
        ];


        foreach($params as $key => $val) {
            $qstring .= $key. '=' .$val. '&';

            // use to get the format index in array
            if($key == 'format') {
                $format = $val;
            }
        }

        return $this->xmlData = simplexml_load_file($indeedUrl.$qstring);
        
    }


    public function loopJobs() {

        $CI =& get_instance();

        $wrapjob = null;
        $counter = 0;

        $xml = self::loadJobs();        
        $baseurl = $CI->config->site_url(). 'job/open_';

        foreach($xml->results->result as $result) { 

            $counter = $counter + 1;
            $redirecturl = $baseurl.getIndeedJobKey($result->url). '.html';
            //$redirecturl = $result->url;

            $data = [
                'jobtitle' => $result->jobtitle,
                'company' => $result->company,
                'location' => $result->formattedLocation,
                'source' => $result->source,
                'description' => $result->snippet,
                'time' => $result->formattedRelativeTime,
                'url' => $redirecturl
            ];

            $wrapjob .= $CI->load->view('templates/tpl_indeed_search_list', $data, TRUE);

            if($counter == 2) {
                $wrapjob .= '<hr class="separator" />';
            }

        }
        
        return $this->loopJobData = $wrapjob;
    }

    public function totalSearchResult() {
        return $this->totalresults = $this->xmlData->totalresults;
    }
    
    public function queryStart() {
        return $this->start = $this->xmlData->start;
    }

    public function queryEnd() {
        return $this->end = $this->xmlData->end;
    }

    // Query note
    public function searchQueryNote() {

        $jobstring = self::loopJobs();
        $location = (isset($_GET['l']) && $_GET['l'] != null) ? $_GET['l'] : 'all places';
        
        $jobsearched = str_replace('-', ' ', $this->query);
        
        $queried = (isset($_GET['q']) && $_GET['q'] != null) ?$_GET['q']. ' jobs in ' .$location : $jobsearched. ' jobs in ' .$location;

        if($jobstring != null) {
            $data = 'Search result/s for <em>' .$queried. '</em>';
        }
        else {
            $data = 'Try searching again!';
        }
        return $data;
    }


}
