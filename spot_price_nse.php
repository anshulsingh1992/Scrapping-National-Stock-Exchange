<?php
/*~ Scrapping National stock  Exchange Gold spot price .
.---------------------------------------------------------------------------.
|
|   Authors: Anshul Singh |
| ------------------------------------------------------------------------- |
| This program is distributed in the hope that it will be useful - WITHOUT  |
| ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
| FITNESS FOR A PARTICULAR PURPOSE.                                         |
'---------------------------------------------------------------------------'
*/
/*ini_set("error_reporting", E_ERROR);
ini_set("error_reporting", E_ALL);
ini_set("display_errors", 1);*/

include_once dirname(dirname(__FILE__)) .'/includes/common.php';
//include_once BASE_PATH.'/includes/simple_html_dom.php';
include_once BASE_PATH.'/includes/inc.common.nseit.php';
include_once BASE_PATH.'/includes/MysqliDb.php';

$mysqli = new Mysqlidb (DB_HOST_INT, DB_USER_INT, DB_PASSWORD_INT, DB_NAME_INT);
echo $date = date('Ymd');

$headers = array(
'Accept'=> '*/*', //remove --
'Accept-Encoding'=> 'gzip, deflate, br',
'Accept-Language'=> 'en-US,en;q=0.9',
'Connection'=> 'keep-alive',
'Cookie'=> 'NSE-TEST-1=1944068106.20480.0000; JSESSIONID=FE4397F6C74D973F93A5EEEE06AD4011.tomcat2; ak_bmsc=9046558E78C2C3A364C293BE07C8DA5317DFF43EDB790000E46DD35FFFE21870~pl03rHBl01cvn1ttAHw2kQf49Pv+6DzGr4i2i5R6JhM4yfk08/Lf4AhdFM2uroBDK+vuGRNHRAZNd9zdFBYVLBSNtPaMf9tC1Epo0sZ/xptNaIELrGCNDgG646gEB2qpOrgtNIVG8ABlrz+829VQotxpG/kKPBuC8U2jMNyz1cYGXXqjhFBZZsI/PIi+rkafV5xO2+1/4826NQ9Gm6L2zZ7hhYNLsnUB6UjWTOhl5WpZ8=; bm_sv=4DC7526B9A92B5F9B765D2A03C503750~l8PCv001uXWny310KZxnursw06x0PsKJ1JBFyjr48JPMcIpjZiwOm8eb6EEKK32kKkGaUVIB45volkaxBsu0Z4pCeWKeYl75K8VsvWqE6bSxyYAy6uVED18d4ugaYHF7Gjba/GvucO9idERDEDmkyBUWURZUNAPIL5BfuoR8wQE=; RT="z=1&dm=nseindia.com&si=3d28a85d-478b-4853-a685-4ffa691f479f&ss=kijvs2yw&sl=0&tt=0&bcn=%2F%2F684fc53c.akstat.io%2F&nu=cb14dca825aeb645c050244f1f75e27a&cl=7dvrb',
'Host'=> 'www1.nseindia.com',
'Referer'=> 'https://www1.nseindia.com/live_market/dynaContent/live_watch/commodity_der_stock_watch.htm',
'Sec-Fetch-Dest'=> 'empty',
'Sec-Fetch-Mode'=> 'cors',
'Sec-Fetch-Site'=> 'same-origin',
'User-Agent'=> 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36',
'X-Requested-With'=> 'XMLHttpRequest',
);

/*$headers = array(
	'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*--/*;q=0.8',
	'Accept-Encoding' => 'gzip, deflate, br',
	'Accept-Language' => 'en-US,en;q=0.5',
	'Connection' => 'keep-alive',
	'Host' => 'www1.nseindia.com',
	'Upgrade-Insecure-Requests' => '1',
	'User-Agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:83.0) Gecko/20100101 Firefox/83.0'
);*/
$param = json_encode(array());

$response = $harvest->curlRequest("https://www1.nseindia.com/live_market/dynaContent/live_watch/fx_tracker/most_active/Spot_price.json",$param,1, 1, $headers);

echo "<pre>";

$responseNSE = json_decode($response, true);
print_r($responseNSE);

echo "=============";
$dataArray = array();
if(isset($responseNSE['data']) && !empty($responseNSE['data'])){
	foreach ($responseNSE['data'] as $key => $valuense) {
		if($valuense['symbol'] == "GOLD" || $valuense['symbol'] == "SILVER"){

			if(array_key_exists("spot_price", $valuense)) {
					$dataArray[$valuense['symbol']][1] =  $valuense;
			}
			if(array_key_exists("spot_price_2", $valuense)){
					$dataArray[$valuense['symbol']][2] =  $valuense;
			}
		}
	}
}
echo "dataArray";
print_r($dataArray);
//exit;
foreach ($dataArray as $key => $nseArray) {

		foreach ($nseArray as $nsekey => $nsevalue) {
				echo " in : $key => ";
				print_r($nsevalue);
				echo $strtime = strtotime($nsevalue['dt']);
				//exit();
				//$strtime = preg_replace("/[^0-9]/", "", $nsevalue['dt'] );

				echo $getdate = date('d-m-Y',$strtime);
				echo " === ";
				echo $getTime = ($nsekey == 1) ? '12.30 PM' : '4.30 PM' ;
				//$getHOur =  date('h',$strtime);
				//$session = (($getHOur == 'session_1') ? 1 : 2);

				//check for same date with session value exists or not , update if yes or insert if not.
				$mysqli->where("post_date",strtotime($getdate));
				$mysqli->where("source","nse");
				$mysqli->where("commodity",$key);
				$mysqli->where("session",$nsekey);
				$nse_exists = $mysqli->getOne("confab_intext_nseit.nse_spot_prices");
				echo $mysqli->getLastQuery();
				echo "entry";
				print_r($nse_exists);
				//exit;
				if(isset($nse_exists) && count($nse_exists) > 1){
					$nse_update_data = array(
						'unit' => $nsevalue['quotation'],
						'price' => ($nsekey == 1) ? str_replace(',', "", $nsevalue['spot_price']) : str_replace(',', "", $nsevalue['spot_price_2']) ,
						'updated_time' => $getTime ,
						'post_time' => strtotime($getdate." ".$getTime),
						'insert_time' => time(),
						'status' => 1,
					);
					$mysqli->where("id",$nse_exists['id']);
					$mysqli->update("confab_intext_nseit.nse_spot_prices", $nse_update_data);
					echo $mysqli->getLastQuery();
					echo " Updated Succsessfully for $key with date $getdate. ";
				}else{
					$nse_data = array(
						'source' => 'nse',
						'commodity' => $key,
						'unit' => $nsevalue['quotation'],
						'price' => ($nsekey == 1) ? str_replace(',', "", $nsevalue['spot_price']) : str_replace(',', "", $nsevalue['spot_price_2']) ,
						'session' => $nsekey,
						'raw_date' => $getdate,
						'updated_time' => $getTime,
						'post_date' => strtotime($getdate),
						'post_time' => strtotime($getdate." ".$getTime),
						'insert_time' => time(),
						'status' => 1,
					);
					$mysqli->setQueryOption("IGNORE");
					$int_nse_id = $mysqli->insert("confab_intext_nseit.nse_spot_prices",$nse_data,1);
					echo "<pre>";
					print_r($nse_data);
				}





			//exit;
		}
}




?>