PFSENSE 2.1
===========

PFsense 2.1 zabbix template

Items
-----

  * **hrMemorySize** => The amount of physical read-write main memory, typically RAM, contained by the host.
  * **hrMemoryUsed** => The amount of the storage represented by this entry that is allocated.
  * **hrProcessorLoad7** => The average, over the last seven minutes, of the percentage of time that this processor was not idle.
  * **hrSwapSize** => The amount of swap memory.
  * **hrSwapUsed** => The amount of used swap memory.
  * **hrSystemMaxProcesses** => The maximum number of process contexts this system can support. If there is no fixed maximum, the value should be zero. On systems that have a fixed maximum, this object can help diagnose failures that occur when this maximum is reached.
  * **hrSystemNumUsers** => The number of user sessions for which this host is storing state information. A session is a collection of processes requiring a single act of user authentication and possibly subject to collective job control.
  * **hrSystemProcesses** => The number of process contexts currently loaded or running on this system.
  * **pfCounterBadOffset** => Number of packets with bad offset.
  * **pfCounterFragment** => Number of fragmented packets.
  * **pfCounterMatch** => Number of packets that matched a filter rule.
  * **pfCounterMemDrop** => Number of packets dropped due to memory limit limitations.
  * **pfCounterNormalize** => Number of normalized packets.
  * **pfCounterShort** => Number of short packets.
  * **pfLimitsFrags** => Maximum number of 'scrub' rules in the ruleset.
  * **pfLimitsSrcNodes** => Maximum number of 'sticky-address' or 'source-track' rulesin the ruleset.
  * **pfLimitsStates** => Maximum number of 'keep state' rules in the ruleset.
  * **pfLogInterfaceIp4BytesIn** => Number of IPv4 bytes passed in on the log interface.
  * **pfLogInterfaceIp4BytesOut** => Number of IPv4 bytes passed out on the log interface.
  * **pfLogInterfaceIp4PktsInDrop** => Number of IPv4 packtes dropped coming in on the log interface.
  * **pfLogInterfaceIp4PktsInPass** => Number of IPv4 packets passed in on the log interface.
  * **pfLogInterfaceIp4PktsOutDrop** => Number of IPv4 packets dropped going out on the log interface.
  * **pfLogInterfaceIp4PktsOutPass** => Number of IPv4 packets passed out on the log interface.
  * **pfLogInterfaceName** => The name of the interface configured with 'set log interface'. If no interface has been configured, the object will be empty.
  * **pfStateTableCount** => Number of entries in the state table.
  * **pfStateTableInserts** => Number of entries inserted into the state table.
  * **pfStateTableRemovals** => Number of entries removed from the state table.
  * **pfStateTableSearches** => Number of Searches against the state table.
  * **pfStatusRunning** => True if pf is currently enabled.
  * **pfStatusRuntime** => Indicates how long pf has been enabled. If pf is not currently enabled, indicates how long it has been disabled. If pf has not been enabled or disabled since the system was started, the value will be "0".
  * **pfTablesTblNumber** => The number of tables on this system.
  * **pfTablesTblSSHlockoutCount** => The number of addresses in the table "sshlockout". ** IMPORTANT ** This table can indicate that someone is trying to force SSH authentication.
  * **pfTimeoutsAdaptiveEnd** => When reaching this number of state entries, all timeouts values become zero, effectively purging all state entries immediately.
  * **pfTimeoutsAdaptiveStart** => When the number of state entries exceeds this value, adaptive scaling begins.
  * **pfTimeoutsFragment** => Seconds before an unassembled fragment is expired.
  * **pfTimeoutsIcmpError** => State after an ICMP error came back in response to an ICMP packet.
  * **pfTimeoutsIcmpFirst** => State after the first packet.
  * **pfTimeoutsInterval** => Interval between purging expired states and fragments.
  * **pfTimeoutsOtherFirst** => State after the first packet.
  * **pfTimeoutsOtherMultiple** => State if both hosts have sent packets.
  * **pfTimeoutsOtherSingle** => State if the source host sends more than one packet but the destination host has never sent one.
  * **pfTimeoutsSrcNode** => Lenght of time to retain a source tracking entry after the last state expires.
  * **pfTimeoutsTcpClosed** => State after one endpoint sends an RST.
  * **pfTimeoutsTcpClosing** => State after the first FIN has been sent.
  * **pfTimeoutsTcpEstablished** => The fully established state.
  * **pfTimeoutsTcpFinWait** => State after both FINs have been exchanged and the connection is closed.
  * **pfTimeoutsTcpFirst** => State after the first packet in a connection.
  * **pfTimeoutsTcpOpening** => State before the destination host ever sends a packet.
  * **pfTimeoutsUdpFirst** => State after the first packet.
  * **pfTimeoutsUdpMultiple** => State if both hosts have sent packets.
  * **pfTimeoutsUdpSingle** => State if the source host sends more than one packet but the destination host has never sent one.
  * **sysContact** => The textual identification of the contact person for this managed node, together with information on how to contact this person.
  * **SysDescr** => A textual description of the entity. This value should include the full name and version identification of the system's hardware type, software operating-system, and networking software. It is mandatory that this only contain printable ASCII characters.
  * **sysLocation** => The physical location of this node (e.g, 'telephone closet, 3rd floor' ).
  * **sysName** => An administratively-assigned name for this managed node. By convention, this is the node's fully-qualified domain name.
  * **sysUptime** => The amount of time since this host was last initialized. Note that this is different from sysUpTime in the SNMPv2-MIB [RFC1907] because sysUpTime is the uptime of the network management portion of the system.
