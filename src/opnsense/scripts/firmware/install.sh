#!/bin/sh

# Copyright (C) 2015-2021 Franco Fichtner <franco@opnsense.org>
# Copyright (C) 2014 Deciso B.V.
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#
# 1. Redistributions of source code must retain the above copyright notice,
#    this list of conditions and the following disclaimer.
#
# 2. Redistributions in binary form must reproduce the above copyright
#    notice, this list of conditions and the following disclaimer in the
#    documentation and/or other materials provided with the distribution.
#
# THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
# INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
# AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
# AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
# OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
# SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
# INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
# CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
# ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.

LOCKFILE=/tmp/pkg_upgrade.progress
PACKAGE=${1}

: > ${LOCKFILE}

echo "***GOT REQUEST TO INSTALL***" >> ${LOCKFILE}
if [ "${PACKAGE#os-}" != "${PACKAGE}" ]; then
	COREPKG=$(opnsense-version -n)
	COREVER=$(opnsense-version -v)
	REPOVER=$(pkg rquery %v ${COREPKG})

	# plugins must pass a version check on up-to-date core package
	if ! php -r "exit(version_compare('${COREVER}','${REPOVER}') >= 0 ? 0 : 1);"; then
		echo "Installation out of date. The update to ${COREPKG}-${REPOVER} is required." >> ${LOCKFILE} 2>&1
		echo '***DONE***' >> ${LOCKFILE}
		exit
	fi
fi
pkg install -y ${PACKAGE} >> ${LOCKFILE} 2>&1
/usr/local/opnsense/scripts/firmware/register.php install ${PACKAGE} >> ${LOCKFILE} 2>&1
pkg autoremove -y >> ${LOCKFILE} 2>&1
echo '***DONE***' >> ${LOCKFILE}
