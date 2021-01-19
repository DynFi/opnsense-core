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
echo gettext('
        Redirect attacks are the purposeful mass-issuing of ICMP type 5 packets. In a normal network, redirects
        to the end stations should not be required. This option enables the NIC to drop all inbound ICMP redirect
        packets without returning a response.
      ');
echo gettext('
        Source routing is another way for an attacker to try to reach non-routable addresses behind your box.
        It can also be used to probe for information about your internal networks. These functions come enabled
        as part of the standard FreeBSD core system.
      ');
echo gettext('
        This option turns off the logging of redirect packets because there is no limit and this could fill
        up your logs consuming your whole hard drive.
      ');
echo gettext('Allow unprivileged access to tap(4) device nodes');
echo gettext('Disable CTRL+ALT+Delete reboot from keyboard.');
echo gettext('Disable Indirect Branch Restricted Speculation (Spectre V2 mitigation)');
echo gettext('Disable the pf ftp proxy handler.');
echo gettext('Do not delay ACK to try and piggyback it onto a data packet');
echo gettext('Do not send ICMP port unreachable messages for closed UDP ports');
echo gettext('Drop SYN-FIN packets (breaks RFC1379, but nobody uses it anyway)');
echo gettext('Drop packets to closed TCP ports without returning a RST');
echo gettext('Enable TCP extended debugging');
echo gettext('Enable privacy settings for IPv6 (RFC 4941)');
echo gettext('Enable sending IPv6 redirects');
echo gettext('Enable/disable sending of ICMP redirects in response to IP packets for which a better,
        and for the sender directly reachable, route and next hop is known.
      ');
echo gettext('Generate SYN cookies for outbound SYN-ACK packets');
echo gettext('Handling of non-IP packets which are not passed to pfil (see if_bridge(4))');
echo gettext('Hide processes running as other groups');
echo gettext('Hide processes running as other users');
echo gettext('Increase UFS read-ahead speeds to match the state of hard drives and NCQ.');
echo gettext('Maximum incoming/outgoing TCP datagram size (receive)');
echo gettext('Maximum incoming/outgoing TCP datagram size (send)');
echo gettext('Maximum outgoing UDP datagram size');
echo gettext('Maximum size of the IP input queue');
echo gettext('Maximum socket buffer size');
echo gettext('Page Table Isolation (Meltdown mitigation, requires reboot.)');
echo gettext('Prefer privacy addresses and use them over the normal addresses');
echo gettext('Randomize PID\'s (see src/sys/kern/kern_fork.c: sysctl_kern_randompid())');
echo gettext('Randomize the ID field in IP packets');
echo gettext('Set ICMP Limits');
echo gettext('Set the ephemeral port range to be lower.');
echo gettext('Set to 0 to disable filtering on the incoming and outgoing member interfaces.');
echo gettext('Set to 1 to additionally filter on the physical interface for locally destined packets');
echo gettext('Set to 1 to enable filtering on the bridge interface');
echo gettext('TCP Offload Engine');
echo gettext('UDP Checksums');
