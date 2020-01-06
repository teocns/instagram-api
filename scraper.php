<?php


require_once __DIR__ . '/account.php';
require_once __DIR__.'/profile.php';
require_once __DIR__.'/story.php';

/**
 * Class IGStoryScraper
 */
class IGStoryScraper
{

    protected $scrapers;

    private $use_validation;

    /**
     * IGStoryScraper constructor.
     * This supports multiple accounts (needed to scrape stories), and each can be assigned to a proxy
     * @param $cookies_array <- Must be array -> [ account1Cookies , account2Cookies ]
     * @param $proxies_array
     */



    public function __construct(array $IGStoryScraperAccounts=null , bool $use_validation= false)
    {

        $this->scrapers = [];
        if ($IGStoryScraperAccounts === null){
            $this->parseStoredAccounts();
        }
        else{
            foreach ($IGStoryScraperAccounts as $acc) {
                $this->AddScraper($acc);
            }
        }

        $this->use_validation = $use_validation;
        function _cast_type($item): IGStoryScraperAccount
        {
            return $item;
        }
    }

    private function parseStoredAccounts(){
        $arr = json_decode(file_get_contents(__DIR__.'/sessions.json'),true);


        foreach ($arr as $proxy => $cookies) {

            // try to get proxy user:password
            $proxy_auth = (function (&$proxy){
                $parts = explode(";",$proxy);
                $parts_cnt = count($parts);
                if ($parts_cnt === 1){
                    return NULL;
                }
                elseif($parts_cnt === 2){
                    $proxy = $parts[0];
                    return $parts[1];

                }
                return NULL;
            })($proxy);



            $this->AddScraper(new IGStoryScraperAccount($cookies,$proxy,$proxy_auth));
        }

        if(count($this->scrapers) < 1){
            throw new Exception('No valid scrapers to initialize.');
        }
    }

    public function AddScraper(IGStoryScraperAccount $account)
    {
        if ($this->use_validation) {
            $account->Validate();
        }
        $this->scrapers[] = $account;
    }

    public function GetRandomScraper() : IGStoryScraperAccount{


        $rand = $this->scrapers;
        shuffle($rand);
        return $rand[0];
    }
    
    public function GetProfile(
        $target_username,
        IGStoryScraperAccount $scraper = null
    )
    {
        if ($scraper === null){
            $scraper = $this->GetRandomScraper();
        }

        $result = $scraper->makeRequest("https://www.instagram.com/$target_username/?__a=1");
        $json = json_decode($result, true);
        if (!json_last_error()) {
            return new Profile();
        }
        return NULL;
    }

    public function GetUserStories($username, IGStoryScraperAccount $scraper = null){

        if ($scraper == null){
            $scraper = $this->GetRandomScraper();
        }


        $username_id = $this->GetProfile($username,$scraper);


        //$scraper->Validate();



        $request_variables = [
            "reel_ids"=>["$username_id"],
            "tag_names"=>[],
            "location_ids"=>[],
            "highlight_reel_ids"=>[],
            "precomposed_overlay"=>false,
            "show_story_viewer_list"=>true,
            "story_viewer_fetch_count"=>50,
            "story_viewer_cursor"=>"",
            "stories_video_dash_manifest"=>false
        ];

        //die(json_encode($request_variables));





        $str = urlencode(json_encode($request_variables));
        $url = "https://www.instagram.com/graphql/query/?query_hash=52a36e788a02a3c612742ed5146f1676&variables=$str";

        $headers = [
            "x-ig-app-id: 936619743392459",
            "x-requested-with: XMLHttpRequest",
            "accept: */*",
            "accept-encoding: gzip, deflate, br",
            "content-type:application/json; charset=utf-8",
            "referer: https://www.instagram.com/$username",
            "x-csrftoken: ".$scraper->cookies['csrftoken']
        ];

        die ($scraper->makeRequest($url,$headers));



    }

    private function prepareCurlOptions(IGStoryScraperAccount $account)
    {
        $cookie_str = "";
        foreach ($account->cookies as $name => $val) {
            $cookie_str .= "$name=$val; ";
        }
        $curl_options = [
            CURLOPT_TIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => 1,
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_BINARYTRANSFER =>1,
            CURLOPT_HEADER => 1,
            CURLOPT_HTTPHEADER => [
                "User-Agent: Google Chrome Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36",
                "Cookie: $cookie_str",
                "referer: https://www.instagram.com/",
                "origin: https://www.instagram.com"
            ]
        ];
        if ($account->proxy_host) {
            $curl_options[CURLOPT_PROXY] = $account->proxy_host;
            if ($account->proxy_authentication) {
                $curl_options[CURLOPT_PROXYUSERPWD] = $account->proxy_authentication;
            }
        }
        return $curl_options;

    }
}







