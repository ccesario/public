import sys
import json
from zabbix_api import ZabbixAPI

 
zabbix_server = "http://127.0.0.1/zabbix"
username = "Admin"
password = "zabbix"
 
#Instanciando a API
conexao = ZabbixAPI(server = zabbix_server, log_level=0)
conexao.login(username, password)


hostmonitor = sys.argv[1]
#print hostmonitor

#versao = conexao.api_version()
#print "Versao do Zabbix Server: ", versao
#hosts = conexao.host.get({"output": "extend", "sortfield": "name"})
host = conexao.host.get({"output": ["hostid","name"], "sortfield": "name", "filter": {"host": hostmonitor}})
#address = hosts.hostinterface.get({"output":'extend', "filter": {"host": "MGL_Assis"}})

#for x in host:
#	print '-----------------\n'
#	print x['hostid'] 
#ip = conexao.hostinterface.get({"output": ["hostid", "ip"], "filter": {"hostid": host[0]['hostid']}})
ips = conexao.hostinterface.get({"output": ["useip","ip","dns"], "filter": {"hostid": host[0]['hostid']}})
#ips = conexao.hostinterface.get({"output": "extend", "filter": {"hostid": host[0]['hostid']}})
#print ips

key = '{#IP}'
lst = []

for i in ips:
	#d=dict(zip(key,str(i["ip"])))
	if i["useip"] == '1':
		d = {key : str(i["ip"])}
	else:
		d = {key : str(i["dns"])}
	lst.append(d)
print json.dumps({'data': lst})
