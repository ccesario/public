<?php
/*
<?xml version="1.0" ?>
<backup>
  <general>
    <backupdir>/tmp/backuptemp</backupdir>
    <logdir>/tmp/backuptemp/logs</logdir>
    <emailadmin/>
    <smtpserver/>
  </general>
  <host>
    <enabled>true</enabled>
    <hostname>host1</hostname>
    <address>192.168.1.1</address>
    <protocol>http</protocol>
    <port>8080</port>
    <username>admin</username>
    <pass>password</pass>
  </host>
</backup>
*/

/* TODO
 - Tratar opcoes
 - Gerar Log - (ok parcial)
 - Definir local de backup (ok parcial)
 - Enviar email de notificacao (verificar realmente a necessidade)
 - Checar arquivo de configuracao antes de iniciar o backup (ok)
 - Possbilidade de executar tanto manual, quanto passando o arquivo de configuracao (opcoes via linha de comando)
 - Permitir opcoes de backup (nopackages, donotbackuprrd, restorearea)
*/


$programExec 		= basename($_SERVER['argv'][0]);
$programVersion 	= "0.0001 beta";
$programAuthor 		= "Carlos Cesario <carloscesario@gmail.com>";
$programName		= "pfsensebkp";	


// function to check PHP version 
// Required is >= 5
function checkPhp()
{
	if (version_compare(PHP_VERSION, '5.0.0', '<')) {
		echo 'PHP 5 or greater is required, running version: ' . PHP_VERSION . "\n";
		exit(1);
	}
}


// Check if extension is loaded
function checkExtension($extName)
{
	if (extension_loaded($extName)) {
		return true;
	}
	else {
		return false;
	}
}


//Create and Check path
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

//Log 
function createLog($logMessage,$logDir)
{
	global $programName;
	
	checkPaths($logDir);

	$datetime = date('[D M d G:i:s Y]');
	$scriptname = basename($_SERVER['argv'][0]);

	$fp = fopen($logDir.'/'.$programName.'-'.date("m-d-y").'.log', 'a+');
	fwrite($fp, "${datetime} (${scriptname}) ${logMessage}"."\n" );
	fclose($fp);
}


// Show help
function showHelp()
{
	global $programName, $programExec, $programVersion, $programAuthor;

$helpProgram = "
$programName $programVersion  -  $programAuthor

Usage: $programExec options

	--help 						:: Show this message
	--config  <config file> 			:: XML config file
	--debug						:: Debug process
";

    echo $helpProgram;
    exit(0);
}



// print messages in console
function printMessage($message)
{
    echo "\n==============================================================================\n";
    echo " $message\n";
    echo "==============================================================================\n";
}


// gets the data from a URL 
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
		$data_file = '';
		$http_url = '';
		$http_code = '';
		$error_flag='ERROR :';

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

		$data_file = curl_exec($ch);
		$http_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);       

		// check Sucesss
		if (($data_file) && ($http_code == '302')) {
				
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
       

			// check Sucesss
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


// Main program

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

	//echo "BACKUP DIR " . $globalcfg['backupdir'];
	checkPaths($globalcfg['backupdir']);


	$hostcfg = array();

	foreach ($xml->host as $hosts) {
		$hostcfg['enabled'] = trim(strtolower(strval($hosts->enabled)));
		$hostcfg['hostname'] = trim(strtolower(strval($hosts->hostname)));
		$hostcfg['address'] = trim(strval($hosts->address)); 
		$hostcfg['protocol'] = trim(strtolower(strval($hosts->protocol))); 
		$hostcfg['port'] = trim(strval($hosts->port));
		$hostcfg['username'] = strval($hosts->username); 
		$hostcfg['pass'] = strval($hosts->pass);

		// Only backup enabled hosts
		if ($hostcfg['enabled']  === "true") {

			$backupdir = $globalcfg['backupdir'].'/'.$hostcfg['hostname'];
			checkPaths($backupdir);
			$backup = createBackup($hostcfg);

			if (!$backup['error']) {
				$fp = fopen($backupdir.'/'.$hostcfg['address'].'-'.date("YmdHis").'.xml', 'w');
				fwrite($fp, $backup['backup']);
				fclose($fp);
				echo "SUCESS: ${hostcfg['hostname']} \t- ${hostcfg['address']} - ${backupdir}\n";
				createLog("SUCESS: ${hostcfg['hostname']} - ${hostcfg['address']} - ${backupdir}",$globalcfg['logdir']);
			}
			else {
				echo "${backup['error']} - ${backupdir}\n";
				createLog("${backup['error']}",$globalcfg['logdir']);
			}
		}
	}
}
?>
