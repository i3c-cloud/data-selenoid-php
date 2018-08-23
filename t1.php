<?php
echo "test1\n";
exit;
include ('wd-common.php');

$GLOBALS['checkCache']=true;

//require 'vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

$GLOBALS['rootDir'] = "/i3c/data/extracted";
$GLOBALS['sharedDir']='/i3c/.shared/selenoid';
$GLOBALS['rootUrl'] = "http://www.rzeszowiak.pl";
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

function processBoxContent($a){
	$e=$a['e'];
	$d=&$a['d'];
	//$advDir=$a['advDir'];
	//$isLabel = true;
	//try {
		$le = $e->findElements(WebDriverBy::className("label"));
	//} catch(NoSuchElementException $ex) {
	//	$isLabel = false;
	//}
	$isLabel = count($le)>0;
	//cho "\n\nisLabel:".$isLabel."\n";
	//cho "innerHTML:".$e->getAttribute('innerHTML')."\n\n";	
	//$ve = $e->findElement(WebDriverBy::className("value"));
	//$labelTxt = $le->getText(); 
	if ($isLabel){
		foreach($le as $nle){
			$ve = $nle->findElement(WebDriverBy::xpath("following-sibling::div"));
			$vle = $ve->getText();
			//cho "fndcnt:".$vle."\ns";
			$labelTxt = $nle->getText();
			switch($labelTxt){
				case 'kategoria :':
					$d['kategoria']=$vle;
					break;
				case 'tytuł :':
					$d['tytuł']=$vle;
					break;
				case 'data dodania :':
					$aa= explode(" ", trim($vle));
					if (strpos(" ".$aa[0],"dziś")>0){
						$aa[0]=date('Y-m-d');
						$aa[1]='00:00:00';
					}
					$d['data']=$aa[0];
					$d['godzina']=$aa[1];
					break;
				case 'wyświetleń :':
					$d['wyświetleń']=$vle;
					break;
				case 'cena :':
					$vle = trim(str_replace("PLN",'',$vle));
					$d['cena']=$vle;
					break;
				case 'pow. domu :':
					$vle = trim(str_replace("m2",'',$vle));
					$d['powDomu']=$vle;
					break;
				case 'pow. dzialki :':
					//$vle = trim(str_replace("ar",'',$vle));
					$d['powDzialki']=$vle;
					break;					
				case 'media :':
					//$vle = trim(str_replace("ar",'',$vle));
					$d['media']=$vle;
					break;
				case 'stan :':
					//$vle = trim(str_replace("ar",'',$vle));
					$d['stan']=$vle;
					break;
				case 'polożenie :':
					//$vle = trim(str_replace("ar",'',$vle));
					$d['miejscowość']=$vle;
					break;
				case 'telefon :':
				case 'wiadomość :':
				case '':
					//noop
					break;					
				default:
					echo "unhandled label:".$labelTxt."\n";
			}
		}
	} else {
		//$isFoto = true;
		echo "checking foto\n";
		//try {
			$lef = $e->findElements(WebDriverBy::id("photos"));
		//} catch(NoSuchElementException $ex) {
		//	$isFoto = false;
		//}
		//$f=0;
		$isFoto = count($lef)>0;
		if ($isFoto){
			echo "foto found\n";
			foreach($lef as $le){
				$lines = $le->findElements(WebDriverBy::className("line"));
				foreach($lines as $l){
					$ah = $l->findElements(WebDriverBy::tagName("a"));
					foreach($ah as $ahr){
						//$f++;
						$bigUrl = $ahr->getAttribute("href");
						$smallUrl = $ahr->findElement(WebDriverBy::tagName("img"))->getAttribute("src");
						$smallUrl = str_replace($GLOBALS['rootUrl'],"",$smallUrl);
						$bigUrl = str_replace($GLOBALS['rootUrl'],"",$bigUrl);
						$d['photos'][]=array(
							'bigUrl'=>$bigUrl,
							'smallUrl'=>$smallUrl
						);
						//file_put_contents($advDir.'/foto/fs'.$f.'.jpg', file_get_contents($smallUrl));
						//file_put_contents($advDir.'/foto/fb'.$f.'.jpg', file_get_contents($bigUrl));
					}
				}
			}
			//$d['photos']=$f;
		} else {
			echo "checking desc\n";
			$lef = $e->findElements(WebDriverBy::className("content"));
			$isDesc = count($lef)>0;
			if ($isDesc){
				echo "desc found\n";
				$con0 = "\n\n===========================\n\n";
				if (!isset($d['opis'])) {
					$d['opis']='';
				} 
				$con=(trim($d['opis'])!='')?$con0:'';
				foreach($lef as $le){
					if (trim($le->getText())!=''){
						$d['opis'].=$con.$le->getText();
						$con=$con0;
					}
				}
			}
		}				
	}
}

function addDir($w,$advDir){	
	$ndir = $advDir.'/'.$w;
	if (!file_exists($ndir)){
		echo "making dir:".$ndir."\n";
		mkdir($ndir);
	}
	return $ndir;
}

function formatToFile($name){
	$name=str_replace("/","_",$name);
	$name=str_replace("?","-",$name);
	$name=str_replace("%","-",$name);
	$name=str_replace("&",".",$name);
	return $name;
}

function checkDetails($wd,$rootFolder,$url){
	$rootUrl = $GLOBALS['rootUrl'];
	$url = str_replace($rootUrl,'',$url);
	$flagFile=$GLOBALS['rootDir'].'/'.$rootFolder.'/'.formatToFile($url);
	
	$srcUrl = $GLOBALS['rootUrl'].$url;
	echo "checkDetails:".$srcUrl;
	if ($GLOBALS['checkCache'] && file_exists($flagFile)) {
		echo "flagFile exist - closing list...\n";
		return true;
	}
	return false;
}

function processDetails($wd,$rootFolder,$url){
	$rootUrl = $GLOBALS['rootUrl'];
	$url = str_replace($rootUrl,'',$url);
	$flagFile=$GLOBALS['rootDir'].'/'.$rootFolder.'/'.formatToFile($url);
	
	$srcUrl = $GLOBALS['rootUrl'].$url;
	echo "processDetails:".$srcUrl;
	if ($GLOBALS['checkCache'] && file_exists($flagFile)) {
		echo "flagFile exist - ignoring data...\n";
		return true;
	}	
	$wd->get($srcUrl);
	
	$wd->wait(15, 1000)->until(
		WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className("box-header"))
	);
	
	// nr ogloszenia
	$el = $wd->findElement(WebDriverBy::className("box-header"));
	$txt = $el->getText();
	$nr = str_replace("Ogłoszenie #","",$txt);
	$d['nr']=trim($nr);
	$ela = $wd->findElements(WebDriverBy::className("ogloszeniebox-content"));
	if (count($ela)>0){
		foreach($ela as $v){
			processBoxContent(array(
				'rooturl'=>$rootUrl,
				'wd'=>$wd,
				'd'=>&$d,
				'e'=>$v
				));
		}
	}
	
	//print_r($d)
	
	
	$advDir = $GLOBALS['advDir'];
	$advSharedDir = $GLOBALS['advSharedDir'];
	
	$advDir = addDir($d['data'],$advDir);
	$advSharedDir = addDir($GLOBALS['timeStampFolder'],$advSharedDir);
	
	
	
	$a = explode("/",$url);
	foreach($a as $v){
		$w = trim($v); 
		if ($w!=''){
			$advDir = addDir($w,$advDir);
			$advSharedDir = addDir($w,$advSharedDir);
		}
	}
	//if (file_exists($advDir.'/foto')){
	//	echo "item already downloaded - ignoring ...\n";
	//	return true;
	//}	
	addDir('img',$advDir);
	addDir('img',$advSharedDir);
	
	// telefon
	$elt = $wd->findElements(WebDriverBy::id("tel"));
	$isTel=(count($elt)>0);
	if ($isTel){
		foreach($elt as $el){
			$tagName = $el->getTagName();
			$el2 = $el->findElement(WebDriverBy::tagName("a"));
			$el2->click();
			$wd->wait()->until(
				function () use ($wd,$el) {
					$eli = $el->findElement(WebDriverBy::tagName("img"));
					return !is_null($eli);
				},
				'Error locating img with tel'
			);
			$el3 = $el->findElement(WebDriverBy::tagName("img"));
			$imgSrc = $el3->getAttribute("src");
			$imgSrc = str_replace("data:image/jpeg;base64,","",$imgSrc);
			$dec = base64_decode($imgSrc);
			if ($GLOBALS['storeAdv']){
				file_put_contents($advDir."/img/t1.jpg",$dec);
			}
			file_put_contents($advSharedDir."/img/t1.jpg",$dec);
			
			$d['tel']='img/t1.jpg';
		}
	}
	if (isset($d['photos']) && count($d['photos'])>0){
		foreach($d['photos'] as $f=>$ph){
			$smallUrl = $ph['smallUrl'];
			$bigUrl = $ph['bigUrl'];
			$srcSmall = 'img/fs'.$f.'.jpg';
			$srcBig = 'img/fb'.$f.'.jpg';
			$d['photos'][$f]['srcSmall']=$srcSmall;
			$d['photos'][$f]['srcBig']=$srcBig;
			if ($GLOBALS['storeAdv']){
				file_put_contents($advDir.'/'.$srcSmall, file_get_contents($GLOBALS['rootUrl'].$smallUrl));
				file_put_contents($advSharedDir.'/'.$srcSmall, $advDir.'/'.$srcSmall);
			} else {
				file_put_contents($advSharedDir.'/'.$srcSmall, file_get_contents($GLOBALS['rootUrl'].$smallUrl));
			}
			if ($GLOBALS['storeAdv']){
				file_put_contents($advDir.'/'.$srcBig, file_get_contents($GLOBALS['rootUrl'].$bigUrl));
				file_put_contents($advSharedDir.'/'.$srcBig, $advDir.'/'.$srcBig);
			} else {
				file_put_contents($advSharedDir.'/'.$srcBig, file_get_contents($GLOBALS['rootUrl'].$bigUrl));
				
			}
		}
	}
	
	//add meta and put to json
	$m = array(
		'srcUrl'=>$srcUrl,
		'rootUrl'=>$rootUrl,
		'extracted'=>$GLOBALS['timeStamp']
	);
	$json = wdJsonEncode(array(
					'meta'=>$m,
					'data'=>$d,
			));

	if ($GLOBALS['storeAdv']){
		file_put_contents($advDir."/index.json",$json);
	}
	file_put_contents($advSharedDir."/index.json",$json);
	ob_start();
?>	
	<html>
	<head><meta charset="utf-8"></head>
	<style>
	.ctable {
		border-spacing: 0px;
	}
	.ctdc {
		padding: 5px; border-left: 4px solid #ccc!important; border-color: #2196F3!important;
		text-align: left;
	}
	.ctdl {
		padding: 5px;
		text-align: right;
		vertical-align: text-top;
	}	
	</style>
	<table class="ctable">
<?php foreach($d as $name => $value){ 
if (isset($GLOBALS['itemHandlers'][$name])){
	$func=$GLOBALS['itemHandlers'][$name]['func'];
	call_user_func($func,array('d'=>&$d,
								'advDir'=>$advDir,
								'name'=>$name,
								'value'=>$value	
								));
} else {
?>
	<tr>
	<td class="ctdl"><?=$name?>:
	</td>
	<td class="ctdc">
		<?=$value?>
	</td>
	</tr>	
<?php }} ?>	
	</html>
<?php
	$indexHtml = ob_get_contents(); 
	ob_end_clean();
	$advIndex = $advDir."/index.html";
	if ($GLOBALS['storeAdv']){
		file_put_contents($advIndex,$indexHtml);
	}
	
	
	$sharedIndex=$advSharedDir."/index.html";
	$sharedHref=str_replace($GLOBALS['advSharedDir'].'/'.$GLOBALS['timeStampFolder'].'/','',$sharedIndex);
	file_put_contents($sharedIndex,$indexHtml);
	file_put_contents($flagFile,$sharedHref);
	$info=array(
		'meta'=>$m,
		'data'=>$d,
		//'advIndex'=>$advIndex,
		'sharedIndex'=>$sharedIndex,
		'sharedHref'=>$sharedHref,
		//'advSharedDir'=>$GLOBALS['advSharedDir']
			
			
	);
	$GLOBALS['parsedItems'][]=$info;
	sharedIndexPosition($info);
	return false;	
}

function tmplOpis($a){
?>
	<tr>
	<td class="ctdl">opis:
	</td>
	<td class="ctdc">
		<?=nl2br($a['value'])?> 
	</td>
	</tr>	
<?php	
}

function tmplTel($a){
?>
	<tr>
	<td class="ctdl">tel:
	</td>
	<td class="ctdc">
		<img src="<?=$a['value']?>"/> 
	</td>
	</tr>	
<?php	
}

function tmplZdjecia($a){
?>
	<tr>
	<td class="ctdl">Zdjęcia:
	</td>
	<td class="ctdc">
		<?php 
		foreach($a['value'] as $f =>$ph){
			$srcSmall = 'img/fs'.$f.'.jpg';
			$srcBig = 'img/fb'.$f.'.jpg';
			?>
				<a href="<?=$srcBig?>" title="">
					<img src="<?=$srcSmall?>" alt="">
				</a>
			<?php		
		}
		?>
	</td>
	</tr>	
<?php	
}

function formatPageNr($pageNr){
	if ($pageNr<10){
		return "0".$pageNr;
	}
	return strval($pageNr);
}

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


$ar = explode("/",$GLOBALS['rootUrl']);
$rootFolder = $ar[(count($ar)-1)];

$advDir=$GLOBALS['rootDir'];
$advSharedDir=$GLOBALS['sharedDir'];

$advDir = addDir($rootFolder,$advDir);
$advSharedDir = addDir($rootFolder,$advSharedDir);


$GLOBALS['advSharedDir']=$advSharedDir;
$GLOBALS['advDir']=$advDir;
//processDetails($wd,$rootFolder,"/jedno-z-ostatnich-takich-miejsc-na-pogorzu-przemyskim-10502234");
//echo "jsonResult:".wdJsonEncode($GLOBALS['parsedItems']);

//exit;

wdEcho("|PACKAGEFOLDER: ".$advSharedDir."\n");

$pa=array();
$pn=array();
for($i=1;$i<17;$i++){
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

wdEcho("|PACKAGEENDED: ".$advSharedDir."\n");


function sharedIndexStart(){
	addDir($GLOBALS['timeStampFolder'],$GLOBALS['advSharedDir']);
	ob_start();
	?>
			<html>
			<head><meta charset="utf-8"></head>
			<style>
			.ctable {
				border-spacing: 0px;
			}
			.ctdc {
				padding: 5px; border-left: 4px solid #ccc!important; border-color: #2196F3!important;
				text-align: left;
			}
			.ctds {
				padding: 5px; border-bottom: 2px solid #ccc!important; border-color: #2196F3!important;
				text-align: left;
			}		
			.ctdl {
				padding: 5px;
				text-align: right;
				vertical-align: text-top;
			}
			
	#dpreview {
	  height: 100%;
	  min-height: 600px;
	  width: 580px;
	  overflow-x: auto;
	  overflow-y: hidden;
	  resize: both;
	  position: relative;
	  z-index: 1;
	}
	
	.ifpreview {
	  width: 580px;
	  min-height: 600px;
	  border: none;
	  position:fixed;
	  height:100%;
	  top:0;
	}
	.bold {
	font-size:16px;
	font-weight: bold;
	
	}		
				
			</style>
	<table class="ctable">
	<tr>
	<td style="vertical-align:top;">		
			<table class="ctable">
<?php 			
		$indexHtml = ob_get_contents(); 
		ob_end_clean();
	
	file_put_contents($GLOBALS['advSharedDir'].'/'.$GLOBALS['timeStampFolder'].'/index.html',$indexHtml);			
	
}

function sharedIndexPosition($info){
	
	$srcUrl=$info['meta']['srcUrl'];
	$posHref=$info['sharedHref'];
	$posTitle=$info['data']['tytuł'];
	$posPrice=$info['data']['cena'];
	$posDataGodz=$info['data']['data'].' '.$info['data']['godzina'];
	$posOpis=substr($info['data']['opis'],0,300).' ...';
	$powDzialki=$info['data']['powDzialki'];
	$powDomu=$info['data']['powDomu'];
	$miejsce=$info['data']['miejscowość'];
	$photos=$info['data']['photos'];
	
	//if (isset($GLOBALS['itemHandlers'][$name])){
	//	$func=$GLOBALS['itemHandlers'][$name]['func'];
	//	call_user_func($func,array('d'=>&$d,
	//			'advDir'=>$advDir,
	//			'name'=>$name,
	//			'value'=>$value
	//	));
	//} else {
		$colMain=3;
		ob_start();
		?>
			<tr>
			<td><?=$name?>:
			</td>
			<td style="text-align:left;">
				<a href="<?=$posHref?>" target="ifpreview"><h4 style="margin-top:5px;margin-bottom:10px;"><?=$posTitle?></h4></a> 
			</td>
			<td class="cdtl">
				cena:&nbsp;&nbsp;&nbsp;<span class="bold"><?=$posPrice ?></span>
			</td>
			</tr>
			<tr>
			<td colspan="<?=$colMain?>">
				<table class="ctable" width="100%">
					<tr><td             >Wystawiony:      </td><td>Pow. domu:      </td><td>Pow. Działki:      </td><td>Miejsce:      </td></tr>
					<tr class="bold"><td><?=$posDataGodz?></td><td><?=$powDomu   ?></td><td><?=$powDzialki   ?></td><td><?=$miejsce ?></td></tr>
				</table>
			</td>
			</tr>
			<td colspan="<?=$colMain?>">
					<?=$posOpis?>
			</td>
			</tr>
			<tr>
			<td colspan="<?=$colMain?>">
	<?php 
	if (is_array($photos)){
	foreach($photos as $k=>$f) {
		$srcBig=dirname($posHref).'/'.$f['srcBig'];
		$srcSmall=dirname($posHref).'/'.$f['srcSmall'];
	?>
					<a href="<?=$srcBig?>" title="" target="ifpreview">
						<img src="<?=$srcSmall?>" alt="" height="80px"/>
					</a>
		
	<?php	
		}} ?>
	
			
			</td>
			</tr>		
			<tr style="display:none;">
			<td class="ctds">&nbsp;
			</td>
			<td class="ctds" id="jsonData" colspan="<?=$colMain-1?>">
				<?=wdJsonEncode($info)?>
			</td>
			</tr>		
			<tr>
			<td class="ctds">żródło:
			</td>
			<td class="ctds" colspan="<?=$colMain-1?>">
				<a href="<?=$srcUrl?>" target="ifpreview"><?=$srcUrl?></a>
			</td>
			</tr>			
	<?php
	$indexHtml = ob_get_contents();
	ob_end_clean();
	if (!file_exists($GLOBALS['advSharedDir'].'/'.$GLOBALS['timeStampFolder'].'/index.html')){
		sharedIndexStart();
	}
	file_put_contents($GLOBALS['advSharedDir'].'/'.$GLOBALS['timeStampFolder'].'/index.html',$indexHtml,FILE_APPEND);
}

function sharedIndexFinal(){
	if (!file_exists($GLOBALS['advSharedDir'].'/'.$GLOBALS['timeStampFolder'].'/index.html')) return;
	ob_start();
?>	
	</table>
	</td><td>
	
	<div id="dpreview">
	<iframe class="ifpreview" name="ifpreview" src=""></iframe>
	</div>
	
	</td>
	</tr>
	</table>
	</html>
	<?php
	$indexHtml = ob_get_contents();
	ob_end_clean();
	
	file_put_contents($GLOBALS['advSharedDir'].'/'.$GLOBALS['timeStampFolder'].'/index.html',$indexHtml,FILE_APPEND);	
}



///

 
//img/ogl/104/mini/o_10443500_0.jpg?re=1968305861
///img/ogl/104/10443500_0.jpg?re=1155599913

echo "end.\n";
