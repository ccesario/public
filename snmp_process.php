#!/usr/bin/php -q
<?php
/* =================================================================================

	snmp_process.php
	Copyright (C) 2013 Carlos Cesario - <carloscesario@gmail.com>
	All rights reserved.
	
	Uses snmp to monitor for get number of named processes running for zabbix
                                                                             
   =================================================================================

*/


/**
 * Program define
*/

define("PROGRAMEXEC",basename($_SERVER['argv'][0]));
define("PROGRAMVERSION", "0.1");
define("PROGRAMADATE", "December 19, 2013");
define("PROGRAMAUTHOR","Carlos Cesario <carloscesario@gmail.com>");
define("PROGRAMNAME","snmp_process");

define("SNMP_TIMEOUT", 5000000); // 5 seconds
define("SNMP_RETRIES", 1);
define("SNMP_MIB","HOST-RESOURCES-MIB::hrSWRunName");


/**
 * printMessage()
 * Function to print messages in CLI
 *
 * @param string $message
 * @return none
*/

function printMessage($message)
{
    echo "\n==============================================================================\n";
    echo " $message\n";
    echo "==============================================================================\n";
}


/**
 * checkExtension()
 * Function to check php extesions
 *
 * @param string $extName PHP Module
 * @return boolean
*/

function checkExtension($extName)
{
	if (extension_loaded($extName)) {
		return true;
	}
	else {
		return false;
	}
}


/**
 * usage()
 * Function to print help menu in CLI
 *
 * @param string $msg
 * @return none
*/

function usage($msg = NULL)
{

if ( strlen($msg) )

	$helpProgram = 
PROGRAMNAME ." ". PROGRAMVERSION ." ". PROGRAMADATE . " - " .  PROGRAMAUTHOR . "

Usage: " . PROGRAMEXEC . " options

	-h \t :: Show this message
	-H \t :: Host to check
	-C \t :: Community name
	-n \t :: Process name (exact)
	-d \t :: Dump all process
";

	echo $helpProgram;
	printMessage("$msg");

    exit(1);
}

/**
 * runSnmpwalk()
 * Function to query snmp data
 *
 * @param string $hostname Hostname server
 * @param string $snmpcommunity SNMP Community string
 * @return array $data 
*/

function runSnmpwalk($hostname, $snmpcommunity, $processname, $dumpdata=false)
{
	$data = array();
	snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
	$proc_array = snmprealwalk($hostname, $snmpcommunity, SNMP_MIB, SNMP_TIMEOUT, SNMP_RETRIES);

	$count = 0;

	if ( count($proc_array) > 1 )
	{
		if ($dumpdata == true) {
			$data = $proc_array;
		}else 
		{
			foreach ( $proc_array as $oid => $proc )
			{
				if ( eregi("^${processname}$", $proc) )
				{
					$count++;
				}
			}
			$data[]=$count;
		}
	}

	return $data;
}



/**
 * Main program 
*/


// Extensions to be verified
$checkextensions = array('snmp');

if (($checkextensions) && (is_array($checkextensions))) {
	foreach ($checkextensions as $i => $extname){
		if (!checkExtension($extname)) {
			printMessage("Extension $extname not loaded");
			exit(1);
		}
	}
}

// Get CLI options
$opts = getopt("H:C:n:d");
$dump = false;

if (count($opts) > 0) {
	foreach ( $opts as $opt => $arg )
	{
		switch($opt)
		{
			case "H":
				$host = $arg;
			break;

			case "C":
				$community = $arg;
			break;

			case "n":
				$process = $arg;
			break;

			case "d":
				$dump = true;
			break;

		} // switch()
	} // foreach()

	if (!isset($host))
		usage("Must specify -H");

	if (!isset($community))
		usage("Must specify -C");

	if ($dump == false) { 
		if (!isset($process))
			usage("Must specify -n");
	}

	if ($dump == false) { 
		if (!isset($process))
			usage("Must specify -n");
	}
	else {
		if (!isset($process))
			$process = "";
	}

	$procs = runSnmpwalk($host,$community,$process,$dump);

	foreach ( $procs as $oid => $procrun ) {
		echo ("$procrun\n");
	}
}
else {
	usage();
}

?>
