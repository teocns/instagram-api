<?php



/*
$proxies = json_decode(file_get_contents('api_cookies.json'),true);

$proxy="";
$cookies="";


foreach ($proxies as $key=>$value){
    $proxy = $key;
    $cookies = $value;
}*/



require_once __DIR__.'/scraper.php';






$handle = new IGStoryScraper();


echo $handle->GetUserStories('taylor_mega');

/*echo $handle->GetUserId(
    'teorzx',
    $handle->GetRandomScraper()
);*/





