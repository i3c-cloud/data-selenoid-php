<?php

include('t3.php');

$startP=isset($argv[1])?$argv[1]:'https://allegro.pl/kategoria/bizuteria-naszyjniki-62222?string=antyki&strategy=NO_FALLBACK&order=m&bmatch=baseline-nbn-col-1-1-0725&p=4';

$res=wdFindFinalPage($startP);

echo "\n\n\nRESULTS: \n\n ".$res;
