#!/bin/sh

# Copyright (C) 2018 Franco Fichtner <franco@opnsense.org>
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

FLOCK="/usr/local/bin/flock"
LOCKFILE="/tmp/dh-parameters.lock"
OPENSSL="/usr/local/bin/openssl"
TMPFILE="/tmp/dh-parameters.${$}"
WANTBITS="1024 2048 4096"

if [ -n "${1}" ]; then
	WANTBITS=${1}
fi

touch ${LOCKFILE}

(
	if ${FLOCK} -n 9; then
		for BITS in ${WANTBITS}; do
			${OPENSSL} dhparam -out ${TMPFILE} ${BITS}
			mv ${TMPFILE} /usr/local/etc/dh-parameters.${BITS}
			# provide a sample file for non-default bit sizes
                        if [ ! -f /usr/local/etc/dh-parameters.${BITS}.sample ]; then
				cp /usr/local/etc/dh-parameters.${BITS} \
				    /usr/local/etc/dh-parameters.${BITS}.sample
			fi
		done
	fi
) 9< ${LOCKFILE}
