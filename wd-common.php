<?php
/* common utilities for PHP webdriver

*/

require 'vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\DesiredCapabilities;

$GLOBALS['rootDir'] = "/data/extracted";
$GLOBALS['_wdSessions']=array();

class wd {

private $wd=null;
private $current=null;
private $elems=null;
private $resources=array();
private $line;

function startSession(){
	$cp = DesiredCapabilities::chrome();
	$cp->setCapability("enableVNC", true);	
	$this->wd = RemoteWebDriver::create("http://selenoid:4444/wd/hub",$cp);
//		array("version"=>"68.0", "browserName"=>"chrome")
//	);
	//return $this->wd;
}

private $result;

private function doError($msg){
	$this->result['ok'] = false;
	$this->result['msg'] = 'Error in line '.$this->line.':'.$msg; 
}

private function check($o,$a){
	$res=true;
	$l=0;	
	foreach ($a as $cmd =>$cdef){
		switch ($cmd){
			case 'hasItem':
				if (!isset($o[($cdef)])){
					doError('Item '.$cdef.' not found in array.');
				}
				break;
			break;
			default:
				doError('Unknown check command:'.$cmd);
		}
		if (!$this->result['ok']){
			break;
		}
		$l++;	
	}
	return $this->result['ok'];	
	
}

function run($a){
	$this->result = array(
		'ok'=>true,
		'msg'=>'',
		'res'=>$this->resources
		);
	if ($this->wd==null){
		$this->startSession();
	}		
	$this->line=0;	
	//dEcho("run/a:".json_encode($a));
	foreach ($a as $ln => $cmda){
	foreach ($cmda as $cmd => $cdef){	
		//cho "cmd:".$cmd."\n";
		switch ($cmd){
			//cursor ops
			case 'get':
				$url = null;	
				if (is_array($cdef)){
					if ($this->check($cdef,[
						'hasItem'=>'url'
					])){
						$url = $cdef['url'];
					}
				} else {
					$url = $cdef;
				}
				if ($url!=null){	
					try {
						$this->wd->get($url);
						$this->current=$this->wd;
					} catch (Exception $ex){
						$this->doError($ex->getMessage());
					}
				}
				break;
			case 'onOneFrom':	
				$elName = $cdef;
				$this->current=null;
				//dEcho("onOneFrom/cdef:".$cdef);
				//dEcho("onOneFrom/resources:".json_encode($this->resources));
				//cho $this->resources[$cdef][0]->getTagName()."\n";
				if (isset($this->resources[$cdef]) && is_array($this->resources[$cdef]) && count($this->resources[$cdef])>0){
					//cho "onOneFrom/resources[$cdef]:".get_class($this->resources[$cdef][0])."\n";
					$this->current=$this->resources[$cdef][0];
				}
				echo "onOneFrom/current/class:".get_class($this->current)."\n";
				break;	
			// elem ops		
			case 'find':
				$this->elems = null;
				foreach($cdef as $mname =>$mdef){
					wdEcho("find/$mname/$mdef");
					switch($mname){
						case 'byClass':						
							if ($this->elems!=null){
								$na = array();
								foreach($this->elems as $k => $elem){
									$els=$elem->findElements(WebDriverBy::className($mdef));
									foreach ($els as $k=>$v){
										$na[]=$v;
									}
								}
								$this->elems = $na;
							} else {
								$this->elems = $this->current->findElements(WebDriverBy::className($mdef));
							}
							wdEcho("find/byClass/items:".count($this->elems));
							break;
						case 'byTag':
							if ($this->elems!=null){
								$na = array();
								foreach($this->elems as $k => $elem){
									$els=$elem->findElements(WebDriverBy::tagName($mdef));
									foreach ($els as $k=>$v){
										$na[]=$v;
									}
								}
								$this->elems = $na;
							} else {
								$this->elems = $this->current->findElements(WebDriverBy::tagName($mdef));
							}
							wdEcho("find/byTag/items:".count($this->elems));							
							break;
						case 'byText':
							if ($this->elems!=null){
								$na = array();
								foreach($this->elems as $k => $elem){
									$els=$elem->findElements(WebDriverBy::xpath("descendant-or-self::*/text()[contains(.,'".$mdef."')]/parent::*"));
									foreach ($els as $k=>$v){
										$na[]=$v;
									}
								}
								$this->elems = $na;
							} else {
								$this->elems = $this->current->findElements(WebDriverBy::xpath("//*[contains(.,'".$mdef."')]"));
							}
							wdecho("find/byText/items:".count($this->elems));
							break;														
					}
					if ($this->elems!=null && is_array($this->elems) && count($this->elems)==0){
						break;
					}
				}
				
				if ($this->elems==null){
					$this->doError('Unknown by clause for find.');
				}
				break;
			case 'storeIn':
				$this->resources[$cdef]=$this->elems;
				if ($this->elems!=null){
					if (is_array($this->elems)){
						wdEcho("storeIn/$cdef/count:".count($this->elems));
					} else {
						wdEcho("storeIn/$cdef/value:".$this->elems);
					}
				}
				break;
			case 'getText':	
				$elName = $cdef;
				$this->elems=null;
				echo "getText/current/class:".get_class($this->current)."\n";
				if ($this->current!=null){
					$this->elems=$this->current->getText();
				}
				break;				
			default:
				$this->doError('Unknown command:'.$cmd);
		}
		if (!$this->result['ok']){
			break;
		}
		$this->line++;	
}}
	$this->result['res']=$this->resources;
	return $this->result;
}

}

function wdRun($sId,$a){
	return $GLOBALS['_wdSessions'][$sId]->run($a);
}

function wdStartSession(){
	$GLOBALS['_wdSessions'][]=new wd();
	return count($GLOBALS['_wdSessions'])-1;
}

function wdIsOK($res){
	return (isset($res['ok']) && $res['ok']==true);
}

function wdRes($r,$name){
	if (isset($r['res'][$name])){
		return $r['res'][$name];
	}
	return null;
}

function wdEcho($msg){
	echo $msg."\n";
}

function wdException($msg){
	throw new Exception($msg);
}