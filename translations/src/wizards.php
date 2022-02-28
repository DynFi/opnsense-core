<?php

/*
 * Copyright (C) 2015-2020 Deciso B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

/* Language support, autogenerated file */
echo gettext('(leave empty for none)');
echo gettext('(leave empty to keep current one)');
echo gettext('1024 bit');
echo gettext('2048 bit');
echo gettext('3072 bit');
echo gettext('4096 bit');
echo gettext('512 bit');
echo gettext('A NetBIOS Scope ID provides an extended naming service for NetBIOS over TCP/IP. The NetBIOS Scope ID isolates NetBIOS traffic on a single network to only those nodes with the same NetBIOS Scope ID.');
echo gettext('A Windows Internet Name Service (WINS) server to provide for connecting clients, which allows them to browse Windows shares. This is typically an Active Directory Domain Controller, designated WINS server, or Samba server.');
echo gettext('A name for this OpenVPN instance, for your reference. It can be set however you like, but is often used to distinguish the purpose of the service (e.g. "Remote Technical Staff").');
echo gettext('A name for your reference, to identify this certificate. This is also known as the certificate\'s "Common Name".');
echo gettext('A name for your reference, to identify this certificate. This is the same as common-name field for other Certificates.');
echo gettext('A reload is now in progress. The wizard will redirect you to the dashboard once the reload is completed.');
echo gettext('Add Certificate Authority');
echo gettext('Add LDAP Server');
echo gettext('Add RADIUS Server');
echo gettext('Add a Server Certificate');
echo gettext('Add a rule to allow all traffic from connected clients to pass across the VPN tunnel.');
echo gettext('Add a rule to permit traffic from clients on the Internet to the OpenVPN server process.');
echo gettext('Add new CA');
echo gettext('Add new Certificate');
echo gettext('Add new LDAP server');
echo gettext('Add new RADIUS server');
echo gettext('Add new Server');
echo gettext('Address Pool');
echo gettext('Address of the LDAP server.');
echo gettext('Address of the RADIUS server.');
echo gettext('Allow DNS servers to be overridden by DHCP/PPP on WAN');
echo gettext('Allow communication between clients connected to this server.');
echo gettext('Allow connected clients to retain their connections if their IP address changes.');
echo gettext('Allow multiple concurrent connections from clients using the same Common Name. This is not generally recommended, but may be needed for some scenarios.');
echo gettext('Auth Digest Algorithm');
echo gettext('Authentication Containers');
echo gettext('Authentication Port');
echo gettext('Authentication Type Selection');
echo gettext('Automatically generate a shared TLS authentication key.');
echo gettext('Block RFC1918 Private Networks');
echo gettext('Block bogon networks');
echo gettext('Block non-Internet routed networks from entering via WAN');
echo gettext('Block private networks from entering via WAN');
echo gettext('Certificate');
echo gettext('Certificate Authority');
echo gettext('Certificate Authority Selection');
echo gettext('Choose a Certificate Authority (CA)');
echo gettext('Choose a Server Certificate');
echo gettext('City');
echo gettext('City or other Locality name (e.g. Middelharnis, Leipzig, Louisville).');
echo gettext('Click \'Reload\' to apply the changes.');
echo gettext('Client Settings');
echo gettext('Compress tunnel packets using the LZO algorithm. Adaptive compression will dynamically disable compression for a period of time if OpenVPN detects that the data in the packets is not being compressed efficiently.');
echo gettext('Compression');
echo gettext('Concurrent Connections');
echo gettext('Configure LAN Interface');
echo gettext('Configure WAN Interface');
echo gettext('Country Code');
echo gettext('Create a New Certificate Authority (CA) Certificate');
echo gettext('Create a New Server Certificate');
echo gettext('Create new Certificate');
echo gettext('Cryptographic Settings');
echo gettext('DH Parameters Length');
echo gettext('DHCP');
echo gettext('DHCP Hostname');
echo gettext('DHCP client configuration');
echo gettext('DNS Default Domain');
echo gettext('DNS Server 1');
echo gettext('DNS Server 2');
echo gettext('DNS Server 3');
echo gettext('DNS Server 4');
echo gettext('DNS server to provide for connecting client systems.');
echo gettext('Description');
echo gettext('Descriptive name');
echo gettext('Descriptive name for the RADIUS server, for your reference.');
echo gettext('Descriptive server name, for your own reference.');
echo gettext('Domain');
echo gettext('Domain name field is invalid');
echo gettext('Duplicate Connections');
echo gettext('Dynamic IP');
echo gettext('Email');
echo gettext('Email address for the Certificate contact. Often the email of the person generating the certificate (i.e. You.)');
echo gettext('Enable DNSSEC Support');
echo gettext('Enable Dial-On-Demand mode');
echo gettext('Enable NetBIOS over TCP/IP. If this option is not set, all NetBIOS-over-TCP/IP options (including WINS) will be disabled.');
echo gettext('Enable Resolver');
echo gettext('Enable authentication of TLS packets.');
echo gettext('Encryption Algorithm');
echo gettext('Enter the hostname (FQDN) of the time server.');
echo gettext('Entire Subtree');
echo gettext('Finish');
echo gettext('Finished!');
echo gettext('Firewall Rule');
echo gettext('Firewall Rule Configuration');
echo gettext('Firewall Rules control what network traffic is permitted. You must add rules to allow traffic to the OpenVPN server\'s IP and port, as well as allowing traffic from connected clients through the tunnel. These rules can be automatically added here, or configured manually after completing the wizard.');
echo gettext('Force all client generated traffic through the tunnel.');
echo gettext('Full State of Province name, not abbreviated (e.g. Zuid Holland, Sachsen, Kentucky).');
echo gettext('Gateway IP Address field is invalid');
echo gettext('General Information');
echo gettext('General OpenVPN Server Information');
echo gettext('General Setup');
echo gettext('General configuration');
echo gettext('Generate TLS Key');
echo gettext('Group Naming Attribute');
echo gettext('Harden DNSSEC data');
echo gettext('Hostname');
echo gettext('Hostname or IP address');
echo gettext('IP Address');
echo gettext('IP Address field is invalid');
echo gettext('IPv4 Configuration Type');
echo gettext('IPv4 Local Network');
echo gettext('IPv4 Remote Network');
echo gettext('IPv4 Tunnel Network');
echo gettext('IPv6 Local Network');
echo gettext('IPv6 Remote Network');
echo gettext('IPv6 Tunnel Network');
echo gettext('If a user DN was supplied above, this password will also be used when performing a bind operation.');
echo gettext('If left blank, an anonymous bind will be done.');
echo gettext('If no qualifying outgoing packets are transmitted for the specified number of seconds, the connection is brought down. An idle timeout of zero disables this feature.');
echo gettext('If you are unsure, leave this set to "Local User Access".');
echo gettext('If you enter a value in this field, then MSS clamping for TCP connections to the value entered above minus 40 (TCP/IP header size) will be in effect. If you leave this field blank, an MSS of 1492 bytes for PPPoE and 1500 bytes for all other connection types will be assumed. This should match the above MTU value in most all cases.');
echo gettext('Inter-Client Communication');
echo gettext('Interface');
echo gettext('Invalid Hostname');
echo gettext('Key length');
echo gettext('LAN IP Address');
echo gettext('LAN IP Address field is invalid');
echo gettext('LDAP');
echo gettext('LDAP Authentication Server List');
echo gettext('LDAP Authentication Server Parameters');
echo gettext('LDAP Bind Password');
echo gettext('LDAP Bind User DN');
echo gettext('LDAP Server Selection');
echo gettext('LDAP Server port, leave blank for the default (389 for TCP, 636 for SSL).');
echo gettext('LDAP servers');
echo gettext('Language');
echo gettext('Length of Diffie-Hellman (DH) key exchange parameters, used for establishing a secure communications channel. As with other such settings, the larger values are more secure, but may be slower in operation.');
echo gettext('Lifetime');
echo gettext('Lifetime in days. This is commonly set to 397 (approximately 1 year).');
echo gettext('Lifetime in days. This is commonly set to 825 (approximately 2 years).');
echo gettext('Local Port');
echo gettext('Local User Access');
echo gettext('Local port upon which OpenVPN will listen for connections. The default port is 1194. Leave this blank to auto-select an unused port.');
echo gettext('MAC Address');
echo gettext('MAC Address field is invalid');
echo gettext('MSS');
echo gettext('MTU');
echo gettext('Member Naming Attribute');
echo gettext('NTP Server');
echo gettext('NTP Server 2');
echo gettext('Name');
echo gettext('NetBIOS Node Type');
echo gettext('NetBIOS Options');
echo gettext('NetBIOS Scope ID');
echo gettext('Network Time Protocol server to provide for connecting client systems.');
echo gettext('Next');
echo gettext('On this screen we will configure the Local Area Network information.');
echo gettext('One Level');
echo gettext('OpenVPN rule');
echo gettext('Organization');
echo gettext('Organization name, often the Company or Group name.');
echo gettext('Override DNS');
echo gettext('PPPoE');
echo gettext('PPPoE Dial on demand');
echo gettext('PPPoE Idle timeout');
echo gettext('PPPoE Password');
echo gettext('PPPoE Service name');
echo gettext('PPPoE Username');
echo gettext('PPPoE configuration');
echo gettext('PPTP');
echo gettext('PPTP Dial on demand');
echo gettext('PPTP Idle timeout');
echo gettext('PPTP Local IP Address');
echo gettext('PPTP Local IP Address field is invalid');
echo gettext('PPTP Password');
echo gettext('PPTP Remote IP Address');
echo gettext('PPTP Remote IP Address field is invalid');
echo gettext('PPTP Username');
echo gettext('PPTP configuration');
echo gettext('Paste in a shared TLS key if one has already been generated.');
echo gettext('Port');
echo gettext('Port used by the RADIUS server for accepting Authentication requests, typically 1812.');
echo gettext('Possible options: b-node (broadcasts), p-node (point-to-point name queries to a WINS server), m-node (broadcast then query name server), and h-node (query name server, then broadcast).');
echo gettext('Primary DNS Server');
echo gettext('Primary DNS Server field is invalid');
echo gettext('Protocol');
echo gettext('Protocol to use for OpenVPN connections. If you are unsure, leave this set to UDP.');
echo gettext('Provide a default domain name to clients.');
echo gettext('Provide a virtual adapter IP address to clients (see Tunnel Network).');
echo gettext('RADIUS Authentication Server List');
echo gettext('RADIUS Authentication Server Parameters');
echo gettext('RADIUS Server Selection');
echo gettext('RADIUS servers');
echo gettext('RFC1918 Networks');
echo gettext('Radius');
echo gettext('Redirect Gateway');
echo gettext('Reload');
echo gettext('Reload Configuration');
echo gettext('Reload in progress');
echo gettext('Root Password');
echo gettext('Root Password Confirmation');
echo gettext('SSL - Encrypted');
echo gettext('Search Scope Base DN');
echo gettext('Search Scope Level');
echo gettext('Secondary DNS Server');
echo gettext('Secondary DNS Server field is invalid');
echo gettext('Select an Authentication Backend Type');
echo gettext('SelectedType');
echo gettext('Semicolon separated. This will be prepended to the search base DN above or you can specify full container path, e.g. CN=Users;DC=example or CN=Users,DC=example,DC=com;OU=OtherUsers,DC=example,DC=com');
echo gettext('Server Certificate Selection');
echo gettext('Server Setup');
echo gettext('Set Root Password');
echo gettext('Set the MTU of the WAN interface. If you leave this field blank, an MTU of 1492 bytes for PPPoE and 1500 bytes for all other connection types will be assumed.');
echo gettext('Set the TOS IP header value of tunnel packets to match the encapsulated packet value.');
echo gettext('Shared Secret');
echo gettext('Size of the key which will be generated. The larger the key, the more security is offers, but larger keys are generally slower to use.');
echo gettext('Specify the maximum number of clients allowed to concurrently connect to this server.');
echo gettext('State or Province');
echo gettext('Static');
echo gettext('Static IP Configuration');
echo gettext('Subnet Mask');
echo gettext('TCP');
echo gettext('TCP - Standard');
echo gettext('TCP4');
echo gettext('TCP6');
echo gettext('TLS Authentication');
echo gettext('TLS Shared Key');
echo gettext('The interface where OpenVPN will listen for incoming connections.');
echo gettext('The method used to authenticate traffic between endpoints. This setting must match on the client and server side, but is otherwise set however you like.');
echo gettext('The method used to encrypt traffic between endpoints. This setting must match on the client and server side, but is otherwise set however you like. Certain algorithms will perform better on different hardware, depending on the availability of supported VPN accelerator chips.');
echo gettext('The protocol used by your LDAP server. It can either be standard TCP or SSL encrypted.');
echo gettext('The value in this field is sent as the DHCP client identifier and hostname when requesting a DHCP lease. Some ISPs may require this (for client identification).');
echo gettext('These are the IPv4 networks that will be accessible from the remote endpoint. Expressed as a comma-separated list of one or more CIDR ranges. You may leave this blank if you don\'t want to add a route to the local network through this tunnel on the remote machine. This is generally set to your LAN network.');
echo gettext('These are the IPv4 networks that will be routed through the tunnel, so that a site-to-site VPN can be established without manually changing the routing tables. Expressed as a comma-separated list of one or more CIDR ranges. If this is a site-to-site VPN, enter the remote LAN/s here. You may leave this blank if you don\'t want a site-to-site VPN.');
echo gettext('These are the IPv6 networks that will be accessible from the remote endpoint. Expressed as a comma-separated list of one or more IP/PREFIX. You may leave this blank if you don\'t want to add a route to the local network through this tunnel on the remote machine. This is generally set to your LAN network.');
echo gettext('These are the IPv6 networks that will be routed through the tunnel, so that a site-to-site VPN can be established without manually changing the routing tables. Expressed as a comma-separated list of one or more IP/PREFIX. If this is a site-to-site VPN, enter the remote LAN/s here. You may leave this blank if you don\'t want a site-to-site VPN.');
echo gettext('This field can be used to modify ("spoof") the MAC address of the WAN interface (may be required with some cable connections). Enter a MAC address in the following format: xx:xx:xx:xx:xx:xx or leave blank.');
echo gettext('This is the IPv4 virtual network used for private communications between this server and client hosts expressed using CIDR (eg. 10.0.8.0/24). The first network address will be assigned to the server virtual interface. The remaining network addresses can optionally be assigned to connecting clients. (see Address Pool)');
echo gettext('This is the IPv6 virtual network used for private communications between this server and client hosts expressed using CIDR (eg. fe80::/64). The first network address will be assigned to the server virtual interface. The remaining network addresses can optionally be assigned to connecting clients. (see Address Pool)');
echo gettext('This option causes the interface to operate in dial-on-demand mode, allowing you to have a virtual full time connection. The interface is configured, but the actual connection of the link is delayed until qualifying outgoing traffic is detected.');
echo gettext('This wizard will guide you through the initial system configuration. The wizard may be stopped at any time by clicking the logo image at the top of the screen.');
echo gettext('Time Server Information');
echo gettext('Time server hostname');
echo gettext('Timezone');
echo gettext('Traffic from clients through VPN');
echo gettext('Traffic from clients to server');
echo gettext('Transport');
echo gettext('Tunnel Settings');
echo gettext('Two-letter ISO country code (e.g. NL, DE, US)');
echo gettext('Type of Server');
echo gettext('Type-of-Service');
echo gettext('Typically "cn" (OpenLDAP, Microsoft AD, and Novell eDirectory)');
echo gettext('Typically "cn" (OpenLDAP, Novell eDirectory), "sAMAccountName" (Microsoft AD)');
echo gettext('Typically "member" (OpenLDAP), "memberOf" (Microsoft AD), "uniqueMember" (Novell eDirectory)');
echo gettext('UDP');
echo gettext('UDP4');
echo gettext('UDP6');
echo gettext('Unbound DNS');
echo gettext('Upstream Gateway');
echo gettext('User Naming Attribute');
echo gettext('WINS Server 1');
echo gettext('WINS Server 2');
echo gettext('When set, this option blocks traffic from IP addresses that are reserved (but not RFC 1918) or not yet assigned by IANA.');
echo gettext('When set, this option blocks traffic from IP addresses that are reserved for private networks as per RFC 1918 (10/8, 172.16/12, 192.168/16) as well as loopback addresses (127/8) and Carrier-grade NAT addresses (100.64/10). This option should only be set for WAN interfaces that use the public IP address space.');
echo gettext('Your configuration is now complete.');
echo gettext('addrpool');
echo gettext('authscope');
echo gettext('authserv');
echo gettext('authtype');
echo gettext('basedn');
echo gettext('certca');
echo gettext('certname');
echo gettext('city');
echo gettext('compression');
echo gettext('concurrentcon');
echo gettext('country');
echo gettext('crypto');
echo gettext('defaultdomain');
echo gettext('descr');
echo gettext('description');
echo gettext('dhparameters');
echo gettext('digest');
echo gettext('dnsserver1');
echo gettext('dnsserver2');
echo gettext('dnsserver3');
echo gettext('dnsserver4');
echo gettext('dummy');
echo gettext('duplicate_cn');
echo gettext('dynip');
echo gettext('email');
echo gettext('generatetlskey');
echo gettext('groupattr');
echo gettext('interclient');
echo gettext('interface');
echo gettext('ip');
echo gettext('keylength');
echo gettext('lifetime');
echo gettext('localnet');
echo gettext('localnetv6');
echo gettext('localport');
echo gettext('memberattr');
echo gettext('name');
echo gettext('nameattr');
echo gettext('nbtenable');
echo gettext('nbtscope');
echo gettext('nbttype');
echo gettext('ntpserver1');
echo gettext('ntpserver2');
echo gettext('organization');
echo gettext('ovpnallow');
echo gettext('ovpnrule');
echo gettext('passdn');
echo gettext('port');
echo gettext('pptplocalsubnet');
echo gettext('redirectgw');
echo gettext('remotenet');
echo gettext('remotenetv6');
echo gettext('scope');
echo gettext('secret');
echo gettext('state');
echo gettext('tlssharedkey');
echo gettext('tos');
echo gettext('transport');
echo gettext('tunnelnet');
echo gettext('tunnelnetv6');
echo gettext('userdn');
echo gettext('winsserver1');
echo gettext('winsserver2');
