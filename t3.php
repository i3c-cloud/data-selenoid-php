<?php
echo "Allegro - find not promoted list script\n";
include ('wd-common.php');


//$startUrl='https://allegro.pl/listing?string=antyki';

function findMaxPage($wdId,$startUrl){
$maxPage = null;	
$result = wdRun($wdId,[
	['get'=>$startUrl],
	['find'=>[
		'byClass'=>'m-pagination__text',
	]],
	['storeIn'=>'maxPageElems'],
	['onOneFrom'=>'maxPageElems'],
	['getText'=>''],
	['storeIn'=>'maxPage']		
	]);	
	echo json_encode($result);
	if (wdIsOk($result)){
		$maxPage = wdRes($result,'maxPage');
	}
return $maxPage;	
}

function checkPage($wdId,$startUrl){
//$found = false;	
$result = wdRun($wdId,[
	['get'=>$startUrl],
	['find'=>[
		'byClass'=>'_1090e81',
		'byTag'=>'h2',
		'byText'=>'Lista ofert'
	]],
	['storeIn'=>'pageElems'],	
	]);	
return count(wdRes($result,'pageElems'))>0;	
}

function wdFindFinalPage($startUrl){
	
echo "<br>URI;".$startUrl;
$uri = $startUrl;
if (substr($uri,0,2)=='/?'){
	$uri = substr($uri,2);
}
$startUrl = preg_replace('/&p=(.*?)[&]?/', '', $uri);
echo "<br>$startUrl:".$startUrl."<br/><br/>";	
	
$GLOBALS['rootUrl'] = "https://allegro.pl";
if (strpos($startUrl,"allegro.pl")==0){
	wdException("Non allegro url.");
	exit;
}
//$wd = wdCreateWebDriver();
$wdId = wdStartSession();

$maxPage = findMaxPage($wdId,$startUrl); 
if ($maxPage==null){
	wdEcho("maxPage==null - exit");
	exit;
}
wdEcho("maxPage:".$maxPage);
//ok
$page=intval($maxPage);
$found=0;
$normalPage=$page;
$promoPage=1;
$diff=$normalPage-$promoPage;
$npage=round($page/2);
while(!$found || $diff>1){
	wdEcho("checkingPage: ".$npage);
	$found = checkPage($wdId,$startUrl.'&p='.$npage);
	wdEcho("checkingPage:found: ".$found);
	//$diff = abs($page-$npage);
	$page=$npage;
	if (!$found){
		$promoPage=$npage;
		//idziemy w góre
		$diff = abs(round(($normalPage-$promoPage)/2));
		wdEcho("checkingPage:goingUpNpage0: ".$npage);
		$npage+=$diff;
		wdEcho("checkingPage:goingUpNpage1: ".$npage);
	} else {
		$normalPage=$npage;
		//idziemy w dol
		$diff = abs(round(($normalPage-$promoPage)/2));
		wdEcho("checkingPage:goingDownNpage0: ".$npage);
		$npage-=$diff;
		wdEcho("checkingPage:goingDownNpage1: ".$npage);
	}
	wdEcho("ndiff:".$diff);	
}
$finalUrl=$startUrl."&p=".$npage."\n";
echo $finalUrl;
return $finalUrl;
}		
//wdEcho(json_encode($result));
//https://allegro.pl/listing?string=antyki