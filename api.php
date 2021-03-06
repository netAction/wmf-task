<?php
class CurrencyConverter {
private function db_connect() {
	$cnf = parse_ini_file("../.my.cnf");
	$dbh = new PDO("mysql:host=".$cnf['host'].";dbname=".$cnf['database'],
		$cnf['user'],$cnf['password'],
		array(PDO::ATTR_PERSISTENT => true) // keep persistent alive
	);
	return $dbh;
} // db_connect


private function getOption($key) {
	$dbh=$this->db_connect();
	$stmt = $dbh->prepare('SELECT options_value FROM options '.
			'WHERE options_key=:key');
	$stmt->bindParam(":key",$key);
	$stmt->execute();
	$result=array();
	$result = $stmt->fetch();
	if ($result) $result = $result["options_value"];
	unset($stmt);
	unset($dbh);
	return $result;
} // getOption


private function setOption($key,$value) {
	$dbh=$this->db_connect();
	$stmt = $dbh->prepare('INSERT INTO options '.
			'(options_key,options_value) '.
			'VALUES(:key,:value) '.
			'ON DUPLICATE KEY '.
			'UPDATE options_value=:value');
	$stmt->bindParam(":key",$key);
	$stmt->bindParam(":value",$value);
	$stmt->execute();
	unset($stmt);
	unset($dbh);
} // setOption


private function updateConversions() {
	$apiUrl='http://toolserver.org/~kaldari/rates.xml';
	// $apiUrl='testRates.xml';
	$xml = @simplexml_load_file($apiUrl);
	if (!$xml) return;	// do not update database on error

	$dbh=$this->db_connect();
	foreach ($xml->conversion as $conversion) {
		$stmt = $dbh->prepare('INSERT INTO conversions (currency,rate) '.
			'VALUES(:currency,:rate) '.
			'ON DUPLICATE KEY '.
			'UPDATE rate=:rate');
		$stmt->bindParam(":currency",$conversion->currency);
		$stmt->bindParam(":rate",$conversion->rate);
		$stmt->execute();
	}
	unset($stmt);
	unset($dbh);

	// update last update entry:
	$this->setOption('lastUpdate',time());
} // updateConversions


// Update database whenever class is used and last update too old
function __construct() {
	// update conversions table when last update more than an hour ago:
	$lastUpdate = $this->getOption('lastUpdate');
	$refreshInterval = $this->getOption('refreshInterval');
	if (!$refreshInterval) {
		$this->setOption('refreshInterval',3600);
		$refreshInterval = 3600;
	}
	if ((!$lastUpdate) || ($lastUpdate<time()-$refreshInterval)) {
		$this->updateConversions();
	}
} // construct


// Convert a value '123.4' or '123,4' or 123.4 into USD
function convert($currency,$amount) {
	$currency = trim($currency);
	$currency = strtoupper($currency);
	$amount = trim($amount);
	$amount = str_replace(',','.',$amount); // ISO numbers
	$amount = floatval($amount);

	$dbh=$this->db_connect();
	$stmt = $dbh->prepare('SELECT rate FROM conversions '.
			'WHERE currency=:currency LIMIT 1');
	$stmt->bindParam(":currency",$currency);
	$stmt->execute();
	$result = $stmt->fetch();
	unset($stmt);
	unset($dbh);

	if (!$result) return false;
	$usd = $amount * $result['rate'];
	return $usd;
} // convert


// Convert a string 'JPY 5000' into 'USD 65.58'
// Arrays of strings are OK too
function convertString($param) {
	// array:
	if (is_array($param)) {
		$strings = $param;
		$result = array();
		foreach ($strings as $string) {
			array_push($result,$this->convertString($string));
		}
		return $result;
	}

	// string:
	$string = trim ($param);
	$string =  preg_split('/\s+/',$string);
	$usd = $this->convert($string[0],$string[1]);
	if ($usd===false) return false;
	return 'USD '.number_format($usd,2);
} // convertString


// get the whole conversions table
function getConversions() {
	$dbh=$this->db_connect();
	$stmt = $dbh->prepare('SELECT currency,rate FROM conversions');
	$stmt->execute();
	$result=array();
	while ($conversion = $stmt->fetch()) {
		$result[$conversion['currency']]=array();
		$result[$conversion['currency']]['rate']=floatval($conversion['rate']);
	}
	unset($stmt);
	unset($dbh);
	return $result;
} // getConversions

// TODO: unit tests

} // class CurrencyConverter



header("Content-type: text/javascript");
// convert a single amount:
/*	$currency = $_GET['currency'];
	$amount = $_GET['amount'];
	$obj = new CurrencyConverter();
	$usd = $obj->convert($currency,$amount);
	echo json_encode(array('usd',$usd));
*/

$obj = new CurrencyConverter();
$result = json_encode($obj->getConversions());
// allow both jsonP and json:
if (isset($_GET["callback"]))
	echo preg_replace("/[^a-zA-Z0-9_]/", "",$_GET["callback"])."(".$result.")";
	else echo $result;

?>
