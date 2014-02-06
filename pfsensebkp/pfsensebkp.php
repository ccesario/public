<?php
/* =================================================================================

        pfsensebkp.php
        Copyright (C) 2012 Carlos Cesario - <carloscesario@gmail.com>
        All rights reserved.
                                                                             
   =================================================================================

        Redistribution and use in source and binary forms, with or without
        modification, are permitted provided that the following conditions are met:

         1. Redistributions of source code must retain the above copyright notice,
                this list of conditions and the following disclaimer.

         2. Redistributions in binary form must reproduce the above copyright
                notice, this list of conditions and the following disclaimer in the
                documentation and/or other materials provided with the distribution.

        THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
        INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
        AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
        AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
        OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
        SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
        INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
        CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
        ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
        POSSIBILITY OF SUCH DAMAGE.
                                                                              
   ================================================================================= */

error_reporting(E_ALL ^ E_NOTICE);

/**
 * Program variables
*/
$programExec 		= basename($_SERVER['argv'][0]);
$programVersion 	= "0.2 09-2013";
$programAuthor 		= "Carlos Cesario <carloscesario@gmail.com>";
$programName		= "pfsensebkp";	

/**
 * CAUTION!!!!
 * Do not change this if you already have encrypted passwords
*/
$privateKey			= 'f24*2c$b3*c9&c7';

/**
 * checkPhp()
 * Function to check php version
 * PHP Version >= 5 is required
 *
 * @param none
 * @return none
*/
function checkPhp()
{
	if (version_compare(PHP_VERSION, '5.0.0', '<')) {
		echo 'PHP 5 or greater is required, running version: ' . PHP_VERSION . "\n";
		exit(1);
	}
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
 * checkPaths()
 * Function to check/create paths
 *
 * @param string $pathName
 * @return none
*/
function checkPaths($pathName)
{
	if ($pathName) {	
		if (!is_dir($pathName)) {
			$mkpath = @mkdir($pathName, '0644' );
			if (!$mkpath) {
				printMessage("Please check directory ${pathName}");
				exit(1);
			}
			else {
				printMessage("Creating ${pathName} backup directory");
			}
		}
	}
}

/**
 * createLog()
 * Function to create log file
 *
 * @param string $logMessage Message 
 * @param string $logDir Directory to save logfile
 * @return none
*/
function createLog($logMessage,$logDir)
{
	global $programName;
	
	//Check path specified 
	checkPaths($logDir);

	$datetime = date('[D M d G:i:s Y]');
	$scriptname = basename($_SERVER['argv'][0]);

	$fp = fopen($logDir.'/'.$programName.'-'.date("m-d-y").'.log', 'a+');
	fwrite($fp, "${datetime} (${scriptname}) ${logMessage}"."\n" );
	fclose($fp);
}

/**
 * deleteOlderFiles()
 * Function to delete older files
 *
 * @param numeric $days Number of days before delete all files
 * @param string  $datadir Data directory backup files
 * @return none
*/
function deleteOlderFiles($days,$datadir)
{
	foreach (glob($datadir."/*.xml") as $file)
	{
		//delete if older
		if (filemtime($file) <= (time()-($days*24*60*60)))
		{
			unlink($file);
		}
	}
}

/**
 * showHelp()
 * Function to print help menu in CLI
 *
 * @param none
 * @return none
*/
function showHelp()
{
	global $programName, $programExec, $programVersion, $programAuthor;

$helpProgram = "
$programName $programVersion  -  $programAuthor

Usage: $programExec options

	--help                                  :: Show this message
	--config       <config file>            :: XML config file
	--debug                                 :: Debug backup process
	--cryptpass   'the secure pass'         :: Encrypt password string - Used with <encryptpass> XML option
	--decryptpass 'encrypted pass'          :: Decrypt encrypted password string
	--deletedays  'number days'             :: Number days before delete older files
";

    echo $helpProgram;
    exit(0);
}


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
 * encryptStr()
 * Function to crypt string
 *
 * @param string $strPlain
 * @param global string $privateKey
 * @return string
*/
function encryptStr($strPlain)
{
	global $privateKey;
	
	$strCrypt = '';
	$privateKeyCoded = base64_encode($privateKey);
	$privateKeyCrypt = crypt($privateKey);
	$strCrypt = strrev($strPlain);
	$strCrypt = base64_encode($strCrypt);
	$strCrypt = $strCrypt.$privateKeyCoded;
	$strCrypt = base64_encode($strCrypt);
	$strCrypt = $privateKeyCrypt.$strCrypt;
	return $strCrypt;
}


/**
 * decryptStr()
 * Function to decrypt ecrypted string
 *
 * @param string $strCrypt
 * @param global string $privateKey
 * @return string
*/
function decryptStr($strCrypt)
{
	global $privateKey;
	
	$strDecrypt = '';
	$privateKeyCoded = base64_encode($privateKey);
	$privateKeyCrypt = crypt($privateKey);
	$strDecrypt = substr($strCrypt, strlen($privateKeyCrypt));
	$strDecrypt = base64_decode($strDecrypt);
	$strDecrypt = str_replace($privateKeyCoded,"",$strDecrypt);
	$strDecrypt = base64_decode($strDecrypt);
	$strDecrypt = strrev($strDecrypt);
	
	if ($strDecrypt != '') {     
		return $strDecrypt;
	}
	else {
		return '[ERROR] - Invalid encrypted password';
	}
}


/**
 * createBackup()
 * Function to create/get backup
 * This function process the HTTP requests from PFSense servers
 *
 * @param array $configHost Data from XML file
 * @return array $data
*/
function createBackup($configHost = array())
{
	global $debugprocess;

	if (($configHost) && (is_array($configHost))) {

		$host_address=$configHost['address'];
		$host_protocol=$configHost['protocol'];
		$host_port=$configHost['port'];
		$host_username=$configHost['username'];
		$host_pass=$configHost['pass'];

		$data = array();
		$ckfile = '';
		$data_file = '';
		$http_url = '';
		$http_code = '';
		$error_flag='ERROR  :';

		$url="${host_protocol}://${host_address}:${host_port}/diag_backup.php";

		// create a cookie file
		// if win
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$ckfile = tempnam ("c:/temp", "CURLCOOKIE");
		}
		// linux
		else {
			$ckfile = tempnam ("/tmp", "CURLCOOKIE");
		}
		

		$ch = curl_init();

		// debug
		// display communication with server
		if ($debugprocess) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			//$fp = fopen("debug.tmp", "w");
			//curl_setopt($ch, CURLOPT_STDERR, $fp);
		}
		
		if ($host_protocol == 'https') {
			//check if certificate is valid
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		//timeout to new connections
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

		//timeout to execute request (curl_exec)
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		//post data
		curl_setopt($ch, CURLOPT_POST, true);

		// post params
		// login and get cookie
		$post_pars=http_build_query(array('usernamefld' => $host_username, 'passwordfld' => $host_pass, 'login' => 'login'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_pars);

		// login and set cookie
		curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);

		$data_file = curl_exec($ch);
		$http_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);       

		// check Success
		if ($http_code == '302') {
				
			// post params 
			// get backup
			$post_pars=http_build_query(array('Submit' => 'download', 'donotbackuprrd' => 'yes'));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $post_pars);

			// read saved cookie file
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile); 
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);

			$data_file = curl_exec($ch);
			$http_url= curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       

			// check Success
			if (($data_file) && ($http_code == '200')){
				$data['backup'] = $data_file;
			}
			else {
				$curl_err = curl_error($ch);
				$data['error'] = "${error_flag} ${curl_err} - ${http_url} - code: ${http_code}";
			}
		}
		else {
			$curl_err = curl_error($ch);
			if ($curl_err)
				$data['error'] = "${error_flag} ${curl_err} - ${http_url} - code: ${http_code}";
			else
				$data['error'] = "${error_flag} ${http_url} - code: ${http_code} - Possible auhtentication error!";
		}
		
		// close conection
		curl_close($ch);

		// remove cookie file
		unlink($ckfile);
		return $data;
	}
}

/**
 * Main program 
*/

// Verify PHP version
checkPhp();


// Extensions to be verify
$checkextensions = array('simplexml', 'curl');

if (($checkextensions) && (is_array($checkextensions))) {
	foreach ($checkextensions as $i => $extname){
		if (!checkExtension($extname)) {
			printMessage("Extension $extname not loaded");
			exit(1);
		}
	}
}

// Get CLI options
$totalArgv = count($argv);
if( $totalArgv > 1 ) {
	for( $param = 1; $param < $totalArgv; $param++ ) {
		switch($argv[$param]) {

		case '--help':
			showHelp();
		break;

		case '--config':
			if (!$argv[($param + 1)]) {
				printMessage("Incorrect params");
				exit(1);
			}
			else {
				$configfile = trim($argv[($param + 1)]);
				$param++;
			}
		break;

		case '--debug':
			$debugprocess = true;
		break;

		case '--cryptpass':
			if (!$argv[($param + 1)]) {
				printMessage("--cryptpass   'the secure pass' ");
				exit(1);
			}
			else {
				$cryptpass = encryptStr($argv[($param + 1)]);
				echo "Encrypted password: " . $cryptpass . "\n";
				exit(0);
			}
		break;

		case '--decryptpass':
			if (!$argv[($param + 1)]) {
				printMessage("--decryptpass 'encrypted pass'");
				exit(1);
			}
			else {
				$decryptpass = decryptStr($argv[($param + 1)]);
				echo "Decrypted password: " . $decryptpass . "\n";
				exit(0);
			}
		break;

		case '--deletedays':
			if (!$argv[($param + 1)]) {
					printMessage("--deletedays 'number days > 0'");
					exit(1);
			}
			else {
				$deletedays = trim($argv[($param + 1)]);
				if (!is_numeric($deletedays)) {
					printMessage("--deletedays param must be numeric value");
					exit(1);
				}
				$param++;
			}
		break;

		default:
			showHelp();
		break;
		}
	}
}
else {
	showHelp();
}


// Validate XML
libxml_use_internal_errors(true);

$xml = simplexml_load_file($configfile);

if (!$xml) {
    echo "Failed loading XML\n";
    foreach(libxml_get_errors() as $error) {
        echo "\t", $error->message;
    }
}
else {
	$globalcfg = array();
	$globalcfg['backupdir'] = strval($xml->general->backupdir); 
	$globalcfg['logdir'] = strval($xml->general->logdir);
	$globalcfg['encryptpass'] = strval($xml->general->encryptpass);  

	checkPaths($globalcfg['backupdir']);

	$hostcfg = array();

	foreach ($xml->host as $hosts) {
		$hostcfg['enabled'] = trim(strtolower(strval($hosts->enabled)));
		$hostcfg['hostname'] = trim(strtolower(strval($hosts->hostname)));
		$hostcfg['address'] = trim(strval($hosts->address)); 
		$hostcfg['protocol'] = trim(strtolower(strval($hosts->protocol))); 
		$hostcfg['port'] = trim(strval($hosts->port));
		$hostcfg['username'] = strval($hosts->username);

		if($globalcfg['encryptpass'] === "true"){		
			$hostcfg['pass'] = decryptStr(strval($hosts->pass));
		}
		else {
			$hostcfg['pass'] = strval($hosts->pass);
		}
		// Only backup enabled hosts
		if ($hostcfg['enabled']  === "true") {

			$backupdir = $globalcfg['backupdir'].'/'.$hostcfg['hostname'];
			checkPaths($backupdir);
			$backup = createBackup($hostcfg);

			if (!$backup['error']) {
				$fp = fopen($backupdir.'/'.$hostcfg['address'].'-'.date("YmdHis").'.xml', 'w');
				fwrite($fp, $backup['backup']);
				fclose($fp);
				echo date('[D M j G:i:s]')." SUCCESS: ${hostcfg['hostname']} \t- ${hostcfg['address']} - ${backupdir}\n";
				createLog("SUCCESS: ${hostcfg['hostname']} - ${hostcfg['address']} - ${backupdir}",$globalcfg['logdir']);
			}
			else {
				echo date('[D M j G:i:s]')." ${backup['error']}\n";
				createLog("${backup['error']}",$globalcfg['logdir']);
			}

			// Delete Older Files
			if ($deletedays > 0)
			{
					deleteOlderFiles($deletedays,$backupdir);
			}
		}
	}
}
?>
