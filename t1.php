<?php
echo "test1\n";
echo "Script stats ...\n";
include ('wd-common.php');

$GLOBALS['checkCache']=true;

//require 'vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
$GLOBALS['pageToStop']=10;
$GLOBALS['rootDir'] = "/i3c/data/extracted";
$GLOBALS['sharedDir']='/i3c/.shared/selenoid';
$GLOBALS['rootDomain']='www.rzeszowiak.pl';
$GLOBALS['rootUrl'] = "http://".$GLOBALS['rootDomain'];
$GLOBALS['idPrefix']='r:';

$GLOBALS['timeStamp'] = date("Y-m-d H:i:s");
$search = array(' ',':');
$replace = array('-','-');
$GLOBALS['parsedItems']=array();
$GLOBALS['storeAdv']=false;
$GLOBALS['timeStampFolder'] = str_replace($search,$replace,$GLOBALS['timeStamp']);


$GLOBALS['itemHandlers'] = array(
			'photos'=> array('func'=>'tmplZdjecia'),
			'tel'=> array('func'=>'tmplTel'),
			'opis'=> array('func'=>'tmplOpis')
				);

include('defs/'.$GLOBALS['rootDomain'].'/domy.php');



//$wd=RemoteWebDriver::create("http://192.168.99.100:4444/wd/hub",$cp);
//$wd=(new wd())->startSession();
$wdId = wdStartSession();
$wdC=$GLOBALS['_wdSessions'][(count($GLOBALS['_wdSessions'])-1)];
$wd=$wdC->startSession();
echo "wdclass:".get_class($wd);
//		array("version"=>"68.0", "browserName"=>"chrome")
//	);
//$wd = wdCreateWebDriver();
addDir('',$GLOBALS['rootDir']);


//$ar = explode("/",$GLOBALS['rootUrl']);
//$rootFolder = $ar[(count($ar)-1)];
$rootFolder=$GLOBALS['rootDomain'];
$advDir=$GLOBALS['rootDir'];
$advSharedDir=$GLOBALS['sharedDir'];

$advDir = addDir($rootFolder,$advDir);
$advSharedDir = addDir($rootFolder,$advSharedDir);


$GLOBALS['advSharedDir']=$advSharedDir;
$GLOBALS['advDir']=$advDir;
//processDetails($wd,$rootFolder,"/jedno-z-ostatnich-takich-miejsc-na-pogorzu-przemyskim-10502234");
//echo "jsonResult:".wdJsonEncode($GLOBALS['parsedItems']);

//exit;

wdEcho("|PACKAGEFOLDER: ".$advSharedDir.'/'.$GLOBALS['timeStampFolder']."\n");

$pa=array();
$pn=array();
for($i=1;$i<$GLOBALS['pageToStop'];$i++){
	$foundStop = false;
	$fi = formatPageNr($i);
	$startPage = "http://www.rzeszowiak.pl/Nieruchomosci-Sprzedam-3070".$fi."1155?r=domy";	
	$wd->get($startPage);
	$elpromo = $wd->findElements(WebDriverBy::className("promobox-title-left"));
	$elnormal = $wd->findElements(WebDriverBy::className("normalbox-title-left"));
	$promFound = false;
	$normFound = false;
	echo "Starting page:".$i."\n";
	$prom = array();
	$norm = array();
	if (count($elpromo)>0){
		$promFound = true;
		foreach($elpromo as $k =>$v){
			$el1 = $v->findElement(WebDriverBy::tagName("a"));
			$url = $el1->getAttribute("href");
			$txt = $el1->getText();			
			$prom[]= array('url'=>$url,
				'txt'=>$txt);		
		}		
	}
	if (count($elnormal)>0){
		$normFound = true;
		foreach($elnormal as $k =>$v){
			$el1 = $v->findElement(WebDriverBy::tagName("a"));
			$url = $el1->getAttribute("href");
			$txt = $el1->getText();
			$norm[]= array('url'=>$url,
				'txt'=>$txt);			
		}
	}
	if (count($prom)>0){
		foreach($prom as $at){
			$url = $at['url'];
			$txt = $at['txt'];
			echo "Page:".$i."\n";
			echo "   Prom. URL:".$url.", ".$txt."\n";
			if (!checkDetails($wd,$rootFolder,$url)){
				$pa[]=$at;
			}
		}
	}
	if (count($norm)>0){
		foreach($norm as $at){
			$url = $at['url'];
			$txt = $at['txt'];
			echo "Page:".$i."\n";			
			echo "   Norm. URL:".$url.", ".$txt."\n";
			if (checkDetails($wd,$rootFolder,$url)){
				echo "found stop-item - exiting ...";
				$foundStop=true;
				break;
			} else {
				$pn[]=$at;
			}
		}
	}
	if ($foundStop){
		break;
	}	
}

$n=0;
$c=count($pn);
$c+=count($pa);
$pn = array_reverse($pn);
$pa = array_reverse($pa);
wdEcho("|NEWITEMS: ".$c."\n");
foreach($pn as $at){
	$url = $at['url'];
	$txt = $at['txt'];
	$n++;
	wdEcho("======= Processing ".$n." of ".$c." - normal");
	processDetails($wd,$rootFolder,$url);
}
foreach($pa as $at){
	$url = $at['url'];
	$txt = $at['txt'];
	$n++;
	wdEcho("======= Processing ".$n." of ".$c." - promo");
	processDetails($wd,$rootFolder,$url);
}


sharedIndexFinal();

wdEcho("|PACKAGEENDED: ".$advSharedDir.'/'.$GLOBALS['timeStampFolder']."\n");






///

 
//img/ogl/104/mini/o_10443500_0.jpg?re=1968305861
///img/ogl/104/10443500_0.jpg?re=1155599913

echo "end.\n";
