#!/usr/bin/php -q
<?php
// Report all errors except E_NOTICE
// This is the default value set in php.ini
error_reporting(E_ALL ^ E_NOTICE);


$hostname = '192.168.1.1';



define("PROGRAMVERSION", "0.1");
define("PROGRAMADATE", "December 30, 2013");
define("PROGRAMAUTHOR","Carlos Cesario <carloscesario@gmail.com>");
define("PROGRAMNAME","ibm_dscheck");
define("PROGRAMEXEC","/opt/IBM_DS/client/SMcli");
define("PROGRAMPARAMS",'-S -quick -c "show storagesubsystem profile;"');
#define("PROGRAMHOSTNAME",basename($_SERVER['argv'][0]));
define("PROGRAMHOSTNAME",$hostname);
define("PROGRAMCOMMAND", PROGRAMEXEC ." ". PROGRAMHOSTNAME ." ". PROGRAMPARAMS);
define("STATUS_ERR","ERROR: ");
define("STATUS_OK","OPTIMAL");


function parserItem($iregex,$idata) {
	$data = null;
	if  ( (preg_match("/$iregex/i", $idata)) ) {
		//echo "idata $idata\n";
		list($num_key, $num_val) = explode(':', $idata);
		$data = trim($num_val);
		//echo "data $data\n";
	}
	return $data;
}


function execScli($program, $beginstring=null, $endstring=null ) {
	$rec = false;

	exec($program,$ret_program);

	foreach ($ret_program as $value) {
		$value = trim($value);
		if  (preg_match("/^$beginstring(.*)/i", $value)) {
			$rec=true;
		}

		if  (!(preg_match("/^$beginstring(.*)/i", $value)) && (preg_match("/^(.*)$endstring(.*)/i", $value)) && (!preg_match("/^$/", $value))) {
			$rec=false;
		}

		if ($rec == true) {
			$data_ret[] = trim($value);
		}
	}
	unset($data_ret[0]);
	return $data_ret;
}

function arrayStatus() {
	$beginstr = 'ARRAYS----';
	$endstr = '----';
	$result = false;
	$retnameArray = null;
	$retstatArray = null;

	$patternNumArr = '^(Number of arrays:\s*([^\s]*)|Total Arrays:\s*([^\s]*))';
	$patternArrName = '^Name:\s*([^\s]*)';
	$patternArryStat = 'Status:\s*([^\s]*)';
	$numArrays = 0;
	$scli = execScli(PROGRAMCOMMAND, $beginstr, $endstr);


	foreach ($scli as $value) {
		$numArrays = parserItem($patternNumArr,$value);
		if  ( $numArrays ) {
			//echo "Total arrays $numArrays\n";
			break;
		}
	}

	if ($numArrays > 0) {
		foreach ($scli as $value) {
			$nameArray = parserItem($patternArrName,$value);
			if ($nameArray) {
				$retnameArray = $nameArray;
				//echo "Array name: $retnameArray\n";
			}
			$statArray =  parserItem($patternArryStat,$value);
			if  ( $statArray ) {
				$retstatArray = $statArray;
				//echo "Array status: $retstatArray\n";
				if (!preg_match("/(optimal)/i", $retstatArray)) {
					$result = false;
					break;
				}
				else {
					$result = true;
				}
			}
		}
	}

	if ($result == false)
		echo STATUS_ERR . "One or more array(s) are not optimal (last seen: $retnameArray | $retstatArray)\n";
	else
		echo STATUS_OK . "\n";
}

arrayStatus();


function batStatus() {
	$beginstr = 'ENCLOSURES----';
	$endstr = '----';
	$result = false;
	$retstatBattery = null;
	$patternBattery="(Batteries|Battery Packs) Detected";
	$patternBatteryStatus="^Battery status";
	$scli = execScli(PROGRAMCOMMAND, $beginstr, $endstr);

	foreach ($scli as $value) {
		$batteryPack = parserItem($patternBattery,$value);
		if  ( $batteryPack ) {
			//echo "Number battery $batteryPack\n";
			break;
		}
	}	
	if ($batteryPack > 0) {
		foreach ($scli as $value) {
			$statBattery =  parserItem($patternBatteryStatus,$value);
			if  ( $statBattery ) {
				$retstatBattery = $statBattery;
				//echo "Battery status: $retstatBattery\n";
				if (!preg_match("/(optimal)/i", $retstatBattery)) {
					$result = false;
					break;
				}
				else {
					$result = true;
				}
			}
		}
	}
	
	if ($result == false)
		echo STATUS_ERR . "Battery status ($retstatBattery)\n";
	else
		echo STATUS_OK . "\n";
}

batStatus();


function powerFanStatus() {
	$beginstr = 'ENCLOSURES----';
	$endstr = '----';
	$result = false;
	$retstatPowerFan = null;
	$patternPowerFan = "^Power-Fan (CRUs|CRUs\/FRUs|Canisters) Detected";
	$patternPowerFanStatus = "^Power-fan (canister|CRU\/FRU) (.*) status";
	$scli = execScli(PROGRAMCOMMAND, $beginstr, $endstr);
	
	foreach ($scli as $value) {
	$powerFanDetect = parserItem($patternPowerFan,$value);
	if  ( $powerFanDetect ) {
	//echo "Power Fan Detected $powerFanDetect\n";
	break;
	}
	}
	
	if ($powerFanDetect > 0) {
	foreach ($scli as $value) {
	$statPowerFan =  parserItem($patternPowerFanStatus,$value);
	if  ( $statPowerFan ) {
	$retstatPowerFan = $statPowerFan;
	//echo "PowerFan status: $retstatPowerFan\n";
	if (!preg_match("/(optimal)/i", $retstatPowerFan)) {
	        $result = false;
	        break;
	}
	else {
	        $result = true;
	}
	}
	}
	}
	
	if ($result == false)
	echo STATUS_ERR . "Power Fan status ($retstatPowerFan)\n";
	else
	echo STATUS_OK . "\n";
	
	}
	
	
	powerFanStatus();
	
	
	
function fanStatus() {
	$beginstr = 'ENCLOSURES----';
	$endstr = '----';
	$result = false;
	$retstatFan = null;
	$patternFan = "^Fans Detected";
	$patternFanStatus = "^Fan Status";
	$scli = execScli(PROGRAMCOMMAND, $beginstr, $endstr);
	
	foreach ($scli as $value) {
		$fanDetect = parserItem($patternFan,$value);
		if  ( $fanDetect ) {
			//echo "Fan(s) Detected $fanDetect\n";
			break;
		}
	}
	
	if ($fanDetect > 0) {
		foreach ($scli as $value) {
			$statFan =  parserItem($patternFanStatus,$value);
			if  ( $statFan ) {
				$retstatFan = $statFan;
				//echo "Fan status: $retstatFan\n";
				if (!preg_match("/(optimal)/i", $retstatFan)) {
					$result = false;
					break;
				}
				else {
					$result = true;
				}
			}
		}
	}
	
	if ($result == false)
		echo STATUS_ERR . "Fan status ($retstatFan)\n";
	else
		echo STATUS_OK . "\n";
	
}


fanStatus();


function tempStatus() {
	$beginstr = 'ENCLOSURES----';
	$endstr = '----';
	$result = false;
	$retstatTemp = null;
	$patternTemp = "^Temperature Sensors Detected";
	$patternTempStatus = "^Temperature sensor status";
	$scli = execScli(PROGRAMCOMMAND, $beginstr, $endstr);
	
	foreach ($scli as $value) {
		$tempDetect = parserItem($patternTemp,$value);
		if  ( $tempDetect ) {
			//echo "Temperature Sensors Detected $tempDetect\n";
			break;
		}
	}
	
	if ($tempDetect > 0) {
		foreach ($scli as $value) {
			$statTemp =  parserItem($patternTempStatus,$value);
			if  ( $statTemp ) {
				$retstatTemp = $statTemp;
				//echo "Temperature sensor status: $retstatTemp\n";
				if (!preg_match("/(optimal)/i", $retstatTemp)) {
					$result = false;
					break;
				}
				else {
					$result = true;
				}
			}
		}
	}
	
	if ($result == false)
		echo STATUS_ERR . "Temperature sensor status ($retstatTemp)\n";
	else
		echo STATUS_OK . "\n";

}


tempStatus();



function sfpStatus() {
	$beginstr = 'ENCLOSURES----';
	$endstr = '----';
	$result = false;
	$retstatSfp = null;
	$patternSfp = "^SFPs Detected";
	$patternSfpStatus = "^SFP status";
	$scli = execScli(PROGRAMCOMMAND, $beginstr, $endstr);
	
	foreach ($scli as $value) {
		$sfpDetect = parserItem($patternSfp,$value);
		if  ( $sfpDetect ) {
			//echo "SFPs Detected $sfpDetect\n";
			break;
		}
	}
	
	if ($sfpDetect > 0) {
		foreach ($scli as $value) {
			$statSfp =  parserItem($patternSfpStatus,$value);
			if  ( $statSfp ) {
				$retstatSfp = $statSfp;
				//echo "SFP status: $retstatSfp\n";
				if (!preg_match("/(optimal)/i", $retstatSfp)) {
					$result = false;
					break;
				}
					else {
					$result = true;
				}
			}
		}
	}
	
	if ($result == false)
		echo STATUS_ERR . "SFP status ($retstatSfp)\n";
	else
		echo STATUS_OK . "\n";

}


sfpStatus();


function powerSupplyStatus() {
	$beginstr = 'ENCLOSURES----';
	$endstr = '----';
	$result = false;
	$retstatPowerSupply = null;
	$patternPowerSupply = "^Power Supplies Detected";
	$patternPowerSupplyStatus = "^Power supply status";
	$scli = execScli(PROGRAMCOMMAND, $beginstr, $endstr);
	
	foreach ($scli as $value) {
		$powerSupplyDetect = parserItem($patternPowerSupply,$value);
		if  ( $powerSupplyDetect ) {
			//echo "Power Supplies Detected $powerSupplyDetect\n";
			break;
		}
	}
	
	if ($powerSupplyDetect > 0) {
		foreach ($scli as $value) {
			$statPowerSupply =  parserItem($patternPowerSupplyStatus,$value);
			if  ( $statPowerSupply ) {
				$retstatPowerSupply = $statPowerSupply;
				//echo "Power supply status: $retstatPowerSupply\n";
				if (!preg_match("/(optimal)/i", $retstatPowerSupply)) {
					$result = false;
					break;
				}
				else {
					$result = true;
				}
			}
		}
	}
	
	if ($result == false)
		echo STATUS_ERR . "Power supply status ($retstatPowerSupply)\n";
	else
		echo STATUS_OK . "\n";

}


powerSupplyStatus();


function driveStatus() {
	$beginstr = 'DRIVES----';
	$endstr = '----';
	$result = false;

	$retslotDrive = null;
	$retstatDrive = null;

	$patternNumDrives = '^Number of drives';
	$patternDriveSlot = '^Drive at Enclosure (.*), Slot (.*)';
	$patternDriveStat = 'Status';
	$numArrays = 0;
	$scli = execScli(PROGRAMCOMMAND, $beginstr, $endstr);


	foreach ($scli as $value) {
		$numDrives = parserItem($patternNumDrives,$value);
		if  ( $numDrives ) {
			//echo "Total drives $numDrives\n";
			break;
		}
	}

	if ($numDrives > 0) {
		foreach ($scli as $value) {
			if ((preg_match("/$patternDriveSlot/i", $value))) {
				$retslotDrive = $value;
				//echo "Slot Drive: $retslotDrive\n";
			}
			$statDrive =  parserItem($patternDriveStat,$value);
			if  ( $statDrive ) {
				$retstatDrive = $statDrive;
				//echo "Drive status: $retstatDrive\n";
				if (!preg_match("/(optimal)/i", $retstatDrive)) {
					$result = false;
					break;
				}
				else {
					$result = true;
				}
			}
		}
	}

	if ($result == false)
		echo STATUS_ERR . "One or more drive(s) are not optimal (last seen: $retslotDrive | $retstatDrive)\n";
	else
		echo STATUS_OK . "\n";
}


driveStatus();


?>
