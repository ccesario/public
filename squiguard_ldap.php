<?php
// based on http://samjlevy.com/2011/02/using-php-and-ldap-to-list-of-members-of-an-active-directory-group/
// pfsense integration by marcelloc and ccesario
// ldapsearch -x -h 192.168.11.1 -p 389 -b OU=Internet,DC=domain,DC=local -D CN=Proxyauth,OU=PROXY,DC=domain,DC=local -w PASS

# AD HOST (required)
$ldap_host = "192.168.1.1";

# AD DIRECTORY DN(required)
$ldap_dn = "OU=INTERNET,DC=domain,DC=local";

# BIND USER(required)
$user_bind = "CN=Proxyauth,OU=PROXY,DC=domain,DC=com";

# PASSWORD BIND(required)
$password = "passwd";

#if you need to apply any prefix or sufix to retreived user
#example: prefix user with domain(required)
#$user_mask="DOMAIN\USER";
$user_mask="USER";

#######################
# End of user options #
#######################

require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");

#mount filesystem writable
conf_mount_rw();

function explode_dn($dn, $with_attributes=0) {
	$result = ldap_explode_dn($dn, $with_attributes);
	if (is_array($result)) {
		foreach($result as $key => $value) {
			$result[$key] = $value;
		}
	}
	return $result;
}

function get_ldap_members($group,$user,$password) {
	global $ldap_host;
	global $ldap_dn;
	$LDAPFieldsToFind = array("member");
	$ldap = ldap_connect($ldap_host) or die("Could not connect to LDAP");

	// OPTIONS TO AD
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION,3);
	ldap_set_option($ldap, LDAP_OPT_REFERRALS,0);

	ldap_bind($ldap, $user, $password) or die("Could not bind to LDAP");

	$results = ldap_search($ldap,$ldap_dn,"cn=" . $group,$LDAPFieldsToFind);

	$member_list = ldap_get_entries($ldap, $results);
	$group_member_details = array();

	if (is_array($member_list[0]))
		foreach($member_list[0] as $list)
			if (is_array($list))
				foreach($list as $member) {
					$ldap_dn_user = preg_replace('/^cn=([^,]+),/i','',$member);
					$member_dn = explode_dn($member);
					if (!empty($member_dn[0])) {
						$member_cn = str_replace("CN=","",$member_dn[0]);
						$member_search = ldap_search($ldap, $ldap_dn_user, "(CN=" . $member_cn . ")");
						$member_details = ldap_get_entries($ldap, $member_search);

						// If group have a other group as member (only 1 level)
						if(is_array($member_details[0]['member'])) {
							//print "############\nmembers\n###########\n";
							//var_dump ($member_details[0]['member']);
							foreach($member_details[0]['member'] as $sub_member) {
								$sub_ldap_dn_user = preg_replace('/^cn=([^,]+),/i','',$sub_member);
								$sub_member_dn = explode_dn($sub_member);
								if (!empty($sub_member_dn[0])) {
									$sub_member_cn = str_replace("CN=","",$sub_member_dn[0]);
									$sub_member_search = ldap_search($ldap, $sub_ldap_dn_user, "(CN=" . $sub_member_cn . ")");
									$sub_member_details = ldap_get_entries($ldap, $sub_member_search);
									$group_member_details[] = array($sub_member_details[0]['samaccountname'][0]);
								}
							}
							//echo "#########################################\nsub_group\n";
							//var_dump($group_member_details);
							//echo "#########################################\n";
                        }
                        else
       						$group_member_details[] = array($member_details[0]['samaccountname'][0]);
					}
				}
	ldap_close($ldap);
	return $group_member_details;
}

//Log info
log_error("Running squidGuard LDAP sync");

// Read Pfsense config
global $config,$g;
$id=0;
$apply_config=0;
if (is_array($config['installedpackages']['squidguardacl']['config'])) {
	foreach($config['installedpackages']['squidguardacl']['config'] as $group) {
		$members="";
		echo  "Group : " . $group['name']."\n";
		$result = get_ldap_members($group['name'],$user_bind,$password);
		asort($result);
		foreach($result as $key => $value) {
			if (preg_match ("/\w+/",$value[0]))
				$members .= "'".preg_replace("/USER/",strtolower($value[0]),$user_mask)."' ";
		}
		if (!empty($members)) {
			if($config['installedpackages']['squidguardacl']['config'][$id]['source'] != $members){
				$config['installedpackages']['squidguardacl']['config'][$id]['source'] = $members;
				$apply_config++;
			}
		}
		echo "\t --> Members : " . $members . "\n\n";
		$id++;
	}
}

if ($apply_config > 0) {
	log_error("squidGuard LDAP sync: user list from LDAP is different from current group, applying new configuration...");
	print "user list from LDAP is different from current group, applying new configuration...";
	write_config();
	include("/usr/local/pkg/squidguard.inc");
	squidguard_resync();
	print "done\n";
}

#mount filesystem read-only
conf_mount_ro();

?>
