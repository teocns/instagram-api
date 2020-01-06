<?php


/**
 * Class IGStoryScraper
 */
class IGStoryScraperAccount
{
    public $cookies;
    public $proxy_host;
    public $proxy_authentication;

    public $was_validated = false;

    /**
     * @param $cookies => assoc array containing the set of cookies extracted from a logged in profile
     * @param null $proxy_host => [optional] ip:port
     * @param null $proxy_password => [optional] user:password
     */
    public function __construct(array $cookies, $proxy_host = null, $proxy_authentication = null)
    {
        $this->cookies = $cookies;

        // Parse proxy


        if ($proxy_host){
            // Validate proxy host

            $proxy_host_parts = explode(':', $proxy_host,2);
            if (count($proxy_host_parts) !== 2 ) {
                throw new Exception('IGStoryScraperAccount: $proxy_host must be an ip:port string');

            }
            $this->proxy_host = $proxy_host;


            // Validate proxy password
            if ($proxy_authentication !== NULL) {
                $proxy_password_parts = explode(':', $proxy_authentication,2);
                if (count($proxy_password_parts) !== 2) {
                    throw new Exception('IGStoryScraperAccount: $proxy_authentication must be an user:password string.');
                }

                $this->proxy_authentication = $proxy_authentication;
            }


        }

    }



    public function saveCookies(){
        $file_text = file_get_contents(__DIR__.'/sessions.json');

        $json = json_decode($file_text,true);
        if (json_last_error() || !is_array($json)){
            $json = [];
        }

        $json_key = $this->proxy_authentication ? $this->proxy_host.';'.$this->proxy_authentication : $this->proxy_host;

        $json[$json_key] = $this->cookies;
        file_put_contents(__DIR__.'/sessions.json',json_encode($json));
    }

    public function Validate()
    {

        if($this->was_validated){
            return;
        }
        $this->was_validated = true;

        $body = $this->makeRequest('https://www.instagram.com/');

        $re = '/(?<=<html)(?:.+)(logged-in)(?:.+)(?=\">)/m';
        preg_match_all($re, $body, $matches, PREG_SET_ORDER, 0);
        if(!@$matches[0][1]){
            throw new Exception('IGStoryScraperAccount: Bad cookies / Not authenticated.');
        }


    }
    public function makeRequest($url,$headers = null){
        $ch = curl_init();
        $cookie_str = "";
        foreach ($this->cookies as $name => $val) {
            $cookie_str .= "$name=$val; ";
        }


        $curl_options = [
            CURLOPT_URL => $url,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_BINARYTRANSFER =>1,
            CURLOPT_HEADER => 1,
            CURLOPT_HTTPHEADER => [
                "User-Agent: Google Chrome Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36",
                "Cookie: $cookie_str",
                "referer: https://www.instagram.com/",
                "origin: https://www.instagram.com"
            ]
        ];

        if ($headers){
            array_merge($curl_options[CURLOPT_HTTPHEADER],$headers);
        }
        if ($this->proxy_host){
            $curl_options[CURLOPT_PROXY] = $this->proxy_host;
            if ($this->proxy_authentication){
                $curl_options[CURLOPT_PROXYUSERPWD] = $this->proxy_authentication;
            }
        }
        curl_setopt_array($ch, $curl_options);


        $raw_result = curl_exec($ch);
        $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($raw_result, 0, $header_len);
        $body = substr($raw_result, $header_len);

        $status_code = null;
        if (
            ($status_code = curl_getinfo($ch,CURLINFO_HTTP_CODE))  === 407 ||
            strpos(curl_error($ch),'407') !== FALSE
        ){

            throw new Exception('IGStoryScraperAccount: Bad proxy authentication.');
        }

        if ($body){


            // parse cookies

            preg_match_all('/^set-cookie:\s*([^;]*)/mi', $header, $matches);

            foreach($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookie_name = array_keys($cookie)[0];
                $this->cookies[$cookie_name] = $cookie[$cookie_name];
            }
            $this->saveCookies();

        }

        return $body;
    }
}


