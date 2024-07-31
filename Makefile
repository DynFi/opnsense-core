# Copyright (c) 2014-2024 Franco Fichtner <franco@opnsense.org>
# Copyright (c) 2022 DynFi
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions
# are met:
#
# 1. Redistributions of source code must retain the above copyright
#    notice, this list of conditions and the following disclaimer.
#
# 2. Redistributions in binary form must reproduce the above copyright
#    notice, this list of conditions and the following disclaimer in the
#    documentation and/or other materials provided with the distribution.
#
# THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
# ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
# IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
# ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
# FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
# DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
# OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
# HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
# OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
# SUCH DAMAGE.

all:
	@cat ${.CURDIR}/README.md | ${PAGER}

.include "Mk/defaults.mk"

CORE_ABI?=	24.1
CORE_PHP?=	82
CORE_PYTHON?=	311

CORE_NICKNAME?=	DynFi Panther

.if exists(${GITVERSION})
CORE_COMMIT!=	${GITVERSION}
.else
CORE_COMMIT=	unknown 0 undefined
.endif

CORE_MESSAGE?=	But why?
CORE_TYPE?=	community

.for REPLACEMENT in ABI PHP PYTHON
. if empty(CORE_${REPLACEMENT})
.  warning Cannot build without CORE_${REPLACEMENT} set
. endif
CORE_MAKE+=	CORE_${REPLACEMENT}=${CORE_${REPLACEMENT}}
.endfor

_CORE_NEXT=	${CORE_ABI:C/\./ /}
.if ${_CORE_NEXT:[2]} == 7 # community
CORE_NEXT!=	expr ${_CORE_NEXT:[1]} + 1
CORE_NEXT:=	${CORE_NEXT}.1
.elif ${_CORE_NEXT:[2]} == 10 # business
CORE_NEXT!=	expr ${_CORE_NEXT:[1]} + 1
CORE_NEXT:=	${CORE_NEXT}.4
CORE_SPACER=	no
.elif ${_CORE_NEXT:[2]} == 1 # community
CORE_NEXT=	${_CORE_NEXT:[1]}
CORE_NEXT:=	${CORE_NEXT}.7
.elif ${_CORE_NEXT:[2]} == 4 # business
CORE_NEXT=	${_CORE_NEXT:[1]}
CORE_NEXT:=	${CORE_NEXT}.10
.else
.error Unsupported minor version for CORE_ABI=${CORE_ABI}
.endif

CORE_VERSION?=	${CORE_COMMIT:[1]}
CORE_REVISION?=	${CORE_COMMIT:[2]}
CORE_HASH?=	${CORE_COMMIT:[3]}

CORE_DEVEL?=	master
CORE_STABLE?=	stable/${CORE_ABI}

_CORE_SERIES=	${CORE_VERSION:S/./ /g}
CORE_SERIES?=	${_CORE_SERIES:[1]}.${_CORE_SERIES:[2]}
.if empty(CORE_SPACER)
CORE_SERIES_FW=	${CORE_SERIES:S/$/ /1}
.else
CORE_SERIES_FW=	${CORE_SERIES}
.endif

.if "${CORE_REVISION}" != "" && "${CORE_REVISION}" != "0"
CORE_PKGVERSION=	${CORE_VERSION}_${CORE_REVISION}
.else
CORE_PKGVERSION=	${CORE_VERSION}
.endif

CORE_PYTHON_DOT=	${CORE_PYTHON:C/./&./1}

.if "${CORE_FLAVOUR}" == OpenSSL
CORE_REPOSITORY?=	${CORE_ABI}/latest
.elif "${CORE_FLAVOUR}" == LibreSSL
CORE_REPOSITORY?=	${CORE_ABI}/libressl
.else
CORE_REPOSITORY?=	unsupported/${CORE_FLAVOUR:tl}
.endif

CORE_MESSAGE?=		Meow!
CORE_NAME?=		dynfi
CORE_TYPE?=		release

CORE_PRODUCT?=		DynFi Firewall
CORE_COMMENT?=		${CORE_PRODUCT} ${CORE_TYPE} package
CORE_MAINTAINER?=	info@dynfi.com
CORE_ORIGIN?=		dynfi/${CORE_NAME}
CORE_PACKAGESITE?=	https://packages.dynfi.com/packages
CORE_WWW?=		https://dynfi.com

CORE_COPYRIGHT_HOLDER?=	DynFi
CORE_COPYRIGHT_WWW?=	https://dynfi.com
CORE_COPYRIGHT_YEARS?=	2019-2024

CORE_DEPENDS_amd64?=	beep \
			bsdinstaller \
			suricata

CORE_DEPENDS?=		ca_root_nss \
			choparp \
			cpustats \
			dhcp6c \
			dnsmasq \
			dpinger \
			expiretable \
			filterlog \
			flock \
			flowd \
			hostapd \
			ifinfo \
			iftop \
			isc-dhcp44-relay \
			isc-dhcp44-server \
			kea \
			lighttpd \
			monit \
			mpd5 \
			ntp \
			openssh-portable \
			openvpn \
			opnsense-installer \
			opnsense-lang \
			opnsense-update \
			pam_opnsense \
			pftop \
			php${CORE_PHP}-ctype \
			php${CORE_PHP}-curl \
			php${CORE_PHP}-dom \
			php${CORE_PHP}-filter \
			php${CORE_PHP}-gettext \
			php${CORE_PHP}-google-api-php-client \
			php${CORE_PHP}-json \
			php${CORE_PHP}-ldap \
			php${CORE_PHP}-pcntl \
			php${CORE_PHP}-pdo \
			php${CORE_PHP}-pear-Crypt_CHAP \
			php${CORE_PHP}-pecl-radius \
			php${CORE_PHP}-phalcon4 \
			php${CORE_PHP}-phpseclib \
			php${CORE_PHP}-session \
			php${CORE_PHP}-simplexml \
			php${CORE_PHP}-sockets \
			php${CORE_PHP}-sqlite3 \
			php${CORE_PHP}-xml \
			php${CORE_PHP}-zlib \
			pkg \
			py${CORE_PYTHON}-Jinja2 \
			py${CORE_PYTHON}-dnspython \
			py${CORE_PYTHON}-netaddr \
			py${CORE_PYTHON}-requests \
			py${CORE_PYTHON}-sqlite3 \
			py${CORE_PYTHON}-ujson \
			py${CORE_PYTHON}-vici \
			radvd \
			rrdtool \
			samplicator \
			strongswan \
			sudo \
			syslog-ng \
			syslogd \
			unbound \
			wpa_supplicant \
			zip \
			${CORE_DEPENDS_${CORE_ARCH}}

WRKDIR?=${.CURDIR}/work
WRKSRC?=${WRKDIR}/src
PKGDIR?=${WRKDIR}/pkg
MFCDIR?=${WRKDIR}/mfc

debug:
	@${VERSIONBIN} ${@} > /dev/null

mount:
	@if [ ! -f ${WRKDIR}/.mount_done ]; then \
	    echo -n "Enabling core.git live mount..."; \
	    sed ${SED_REPLACE} ${.CURDIR}/src/opnsense/version/core.in > \
	        ${.CURDIR}/src/opnsense/version/core; \
	    mount_unionfs ${.CURDIR}/src ${LOCALBASE}; \
	    touch ${WRKDIR}/.mount_done; \
	    echo "done"; \
	    service configd restart; \
	fi

umount:
	@if [ -f ${WRKDIR}/.mount_done ]; then \
	    echo -n "Disabling core.git live mount..."; \
	    umount -f "<above>:${.CURDIR}/src"; \
	    rm ${WRKDIR}/.mount_done; \
	    echo "done"; \
	    service configd restart; \
	fi

manifest:
	@echo "name: \"${CORE_NAME}\""
	@echo "version: \"${CORE_PKGVERSION}\""
	@echo "origin: \"${CORE_ORIGIN}\""
	@echo "comment: \"${CORE_COMMENT}\""
	@echo "desc: \"${CORE_HASH}\""
	@echo "maintainer: \"${CORE_MAINTAINER}\""
	@echo "www: \"${CORE_WWW}\""
	@echo "message: \"${CORE_MESSAGE}\""
	@echo "categories: [ \"sysutils\", \"www\" ]"
	@echo "licenselogic: \"single\""
	@echo "licenses: [ \"BSD2CLAUSE\" ]"
	@echo "prefix: ${LOCALBASE}"
	@echo "vital: true"
	@echo "deps: {"
	@for CORE_DEPEND in ${CORE_DEPENDS}; do \
		if ! ${PKG} query '  %n: { version: "%v", origin: "%o" }' \
		    $${CORE_DEPEND}; then \
			echo ">>> Missing dependency: $${CORE_DEPEND}" >&2; \
			exit 1; \
		fi; \
	done
	@echo "}"
	@if [ -f ${WRKSRC}${LOCALBASE}/opnsense/version/core ]; then \
	    echo "annotations $$(cat ${WRKSRC}${LOCALBASE}/opnsense/version/core)"; \
	fi

.if ${.TARGETS:Mupgrade}
# lighter package format for quick completion
PKG_FORMAT?=	-f tar
.endif

PKG_SCRIPTS=	+PRE_INSTALL +POST_INSTALL \
		+PRE_UPGRADE +POST_UPGRADE \
		+PRE_DEINSTALL +POST_DEINSTALL

scripts:
.for PKG_SCRIPT in ${PKG_SCRIPTS}
	@if [ -f ${.CURDIR}/${PKG_SCRIPT} ]; then \
		cp -- ${.CURDIR}/${PKG_SCRIPT} ${DESTDIR}/; \
	fi
.endfor

install:
	@${CORE_MAKE} -C ${.CURDIR}/contrib install DESTDIR=${DESTDIR}
	@${CORE_MAKE} -C ${.CURDIR}/src install DESTDIR=${DESTDIR} ${MAKE_REPLACE}
.if exists(${LOCALBASE}/opnsense/www/index.php)
	# try to update the current system if it looks like one
	@touch ${LOCALBASE}/opnsense/www/index.php
.endif

collect:
	@(cd ${.CURDIR}/src; find * -type f) | while read FILE; do \
		if [ -f ${DESTDIR}${LOCALBASE}/$${FILE} ]; then \
			tar -C ${DESTDIR}${LOCALBASE} -cpf - $${FILE} | \
			    tar -C ${.CURDIR}/src -xpf -; \
		fi; \
	done

bootstrap:
	@${CORE_MAKE} -C ${.CURDIR}/src install-bootstrap DESTDIR=${DESTDIR} \
	    NO_SAMPLE=please ${MAKE_REPLACE}

plist:
	@(${CORE_MAKE} -C ${.CURDIR}/contrib plist && \
	    ${CORE_MAKE} -C ${.CURDIR}/src plist) | sort

plist-fix:
	@${CORE_MAKE} DESTDIR=${DESTDIR} plist > ${.CURDIR}/plist

plist-check:
	@mkdir -p ${WRKDIR}
	@${CORE_MAKE} DESTDIR=${DESTDIR} plist > ${WRKDIR}/plist.new
	@cat ${.CURDIR}/plist > ${WRKDIR}/plist.old
	@if ! diff -q ${WRKDIR}/plist.old ${WRKDIR}/plist.new > /dev/null ; then \
		diff -u ${WRKDIR}/plist.old ${WRKDIR}/plist.new || true; \
		echo ">>> Package file lists do not match.  Please run 'make plist-fix'." >&2; \
		rm ${WRKDIR}/plist.*; \
		exit 1; \
	fi
	@rm ${WRKDIR}/plist.*

metadata:
	@mkdir -p ${DESTDIR}
	@${CORE_MAKE} DESTDIR=${DESTDIR} scripts
	@${CORE_MAKE} DESTDIR=${DESTDIR} manifest > ${DESTDIR}/+MANIFEST
	@${CORE_MAKE} DESTDIR=${DESTDIR} plist > ${DESTDIR}/plist

package-check:
	@if [ -f ${WRKDIR}/.mount_done ]; then \
		echo ">>> Cannot continue with live mount.  Please run 'make umount'." >&2; \
		exit 1; \
	fi

package: plist-check package-check clean-wrksrc
.for CORE_DEPEND in ${CORE_DEPENDS}
	@if ! ${PKG} info ${CORE_DEPEND} > /dev/null; then ${PKG} install -yfA ${CORE_DEPEND}; fi
.endfor
	@echo -n ">>> Staging files for ${CORE_NAME}-${CORE_PKGVERSION}..."
	@${CORE_MAKE} DESTDIR=${WRKSRC} install
	@echo " done"
	@echo ">>> Generated version info for ${CORE_NAME}-${CORE_PKGVERSION}:"
	@cat ${WRKSRC}${LOCALBASE}/opnsense/version/core
	@echo -n ">>> Generating metadata for ${CORE_NAME}-${CORE_PKGVERSION}..."
	@${CORE_MAKE} DESTDIR=${WRKSRC} metadata
	@echo " done"
	@echo ">>> Packaging files for ${CORE_NAME}-${CORE_PKGVERSION}:"
	@PORTSDIR=${.CURDIR} ${PKG} create ${PKG_FORMAT} -v -m ${WRKSRC} \
	    -r ${WRKSRC} -p ${WRKSRC}/plist -o ${PKGDIR}

upgrade-check:
	@if ! ${PKG} info ${CORE_NAME} > /dev/null; then \
		echo ">>> Cannot find package.  Please run 'opnsense-update -t ${CORE_NAME}'" >&2; \
		exit 1; \
	fi
	@if [ "$$(${VERSIONBIN} -vH)" = "${CORE_PKGVERSION} ${CORE_HASH}" ]; then \
		echo "Installed version already matches ${CORE_PKGVERSION} ${CORE_HASH}" >&2; \
		exit 1; \
	fi

upgrade: upgrade-check clean-pkgdir package
	@${PKG} delete -fy ${CORE_NAME} || true
	@${PKG} add ${PKGDIR}/*.pkg
	@${.CURDIR}/src/sbin/pluginctl -c webgui

lint-shell:
	@find ${.CURDIR}/src ${.CURDIR}/Scripts \
	    -name "*.sh" -type f -print0 | xargs -0 -n1 sh -n

lint-xml:
	@find ${.CURDIR}/src ${.CURDIR}/Scripts \
	    -name "*.xml*" -type f -print0 | xargs -0 -n1 xmllint --noout

lint-model:
	@for MODEL in $$(find ${.CURDIR}/src/opnsense/mvc/app/models -depth 3 \
	    -name "*.xml"); do \
		(xmllint $${MODEL} --xpath '//*[@type and not(@type="ArrayField") and (not(Required) or Required="N") and Default]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} has a spurious default value set"; \
		done; \
		(xmllint $${MODEL} --xpath '//*[@type and not(@type="ArrayField") and Default=""]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} has an empty default value set"; \
		done; \
		(xmllint $${MODEL} --xpath '//*[@type and not(@type="ArrayField") and BlankDesc="None"]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} blank description is the default"; \
		done; \
		(xmllint $${MODEL} --xpath '//*[@type and not(@type="ArrayField") and BlankDesc and Required="Y"]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} blank description not applicable on required field"; \
		done; \
		(xmllint $${MODEL} --xpath '//*[@type and not(@type="ArrayField") and BlankDesc and Multiple="Y"]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} blank description not applicable on multiple field"; \
		done; \
		(xmllint $${MODEL} --xpath '//*[@type and not(@type="ArrayField") and Multiple="N"]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} Multiple=N is the default"; \
		done; \
		(xmllint $${MODEL} --xpath '//*[@type and not(@type="ArrayField") and Required="N"]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} Required=N is the default"; \
		done; \
		(xmllint $${MODEL} --xpath '//*[@type and not(@type="ArrayField") and OptionValues[default[not(@value)] or multiple[not(@value)] or required[not(@value)]]]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} option element default/multiple/required without value attribute"; \
		done; \
		(xmllint $${MODEL} --xpath '//*[@type="CSVListField" and Mask and (not(MaskPerItem) or MaskPerItem=N)]' 2> /dev/null | grep '^<' || true) | while read LINE; do \
			echo "$${MODEL}: $${LINE} uses Mask regex with MaskPerItem=N"; \
		done; \
	done

SCRIPTDIRS!=	find ${.CURDIR}/src/opnsense/scripts -type d -depth 1

lint-exec:
.for DIR in ${.CURDIR}/src/etc/rc.d ${.CURDIR}/src/etc/rc.syshook.d ${SCRIPTDIRS}
.if exists(${DIR})
	@find ${DIR} -path '**/htdocs_default' -prune -o -type f \
	    ! -name "*.xml" ! -name "*.csv" ! -name "*.sql" -print0 | \
	    xargs -0 -t -n1 test -x || \
	    (echo "Missing executable permission in ${DIR}"; exit 1)
.endif
.endfor

LINTBIN?=	${.CURDIR}/contrib/parallel-lint/parallel-lint

lint-php:
	@${LINTBIN} src

lint: plist-check lint-shell lint-xml lint-model lint-exec lint-php

sweep:
	find ${.CURDIR}/src -type f -name "*.map" -print0 | \
	    xargs -0 -n1 rm
	if grep -nr sourceMappingURL= ${.CURDIR}/src; then \
		echo "Mentions of sourceMappingURL must be removed"; \
		exit 1; \
	fi
	find ${.CURDIR}/src ! -name "*.min.*" ! -name "*.svg" \
	    ! -name "*.ser" -type f -print0 | \
	    xargs -0 -n1 ${.CURDIR}/Scripts/cleanfile
	find ${.CURDIR}/Scripts ${.CURDIR}/.github -type f -print0 | \
	    xargs -0 -n1 ${.CURDIR}/Scripts/cleanfile
	find ${.CURDIR} -type f -depth 1 -print0 | \
	    xargs -0 -n1 ${.CURDIR}/Scripts/cleanfile

STYLEDIRS?=	src/etc/inc src/opnsense

style-python: debug
	@pycodestyle-${CORE_PYTHON_DOT} --ignore=E501 ${.CURDIR}/src || true

style-php: debug
	@: > ${WRKDIR}/style.out
.for STYLEDIR in ${STYLEDIRS}
	@(phpcs --standard=ruleset.xml ${.CURDIR}/${STYLEDIR} \
	    || true) >> ${WRKDIR}/style.out
.endfor
	@echo -n "Total number of style warnings: "
	@grep '| WARNING' ${WRKDIR}/style.out | wc -l
	@echo -n "Total number of style errors:   "
	@grep '| ERROR' ${WRKDIR}/style.out | wc -l
	@cat ${WRKDIR}/style.out | ${PAGER}
	@rm ${WRKDIR}/style.out

style-fix: debug
.for STYLEDIR in ${STYLEDIRS}
	phpcbf --standard=ruleset.xml ${.CURDIR}/${STYLEDIR} || true
.endfor

style-model:
	@for MODEL in $$(find ${.CURDIR}/src/opnsense/mvc/app/models -depth 3 \
	    -name "*.xml"); do \
		perl -i -pe 's/<default>(.*?)<\/default>/<Default>$$1<\/Default>/g' $${MODEL}; \
		perl -i -pe 's/<multiple>(.*?)<\/multiple>/<Multiple>$$1<\/Multiple>/g' $${MODEL}; \
		perl -i -pe 's/<required>(.*?)<\/required>/<Required>$$1<\/Required>/g' $${MODEL}; \
		perl -i -pe 's/<mask>(.*?)<\/mask>/<Mask>$$1<\/Mask>/g' $${MODEL}; \
	done

style: style-python style-php

license: debug
	@${.CURDIR}/Scripts/license > ${.CURDIR}/LICENSE

sync: license plist-fix

ARGS=	diff mfc

# handle argument expansion for required targets
.for TARGET in ${.TARGETS}
_TARGET=		${TARGET:C/\-.*//}
.if ${_TARGET} != ${TARGET}
.for ARGUMENT in ${ARGS}
.if ${_TARGET} == ${ARGUMENT}
${_TARGET}_ARGS+=	${TARGET:C/^[^\-]*(\-|\$)//:S/,/ /g}
${TARGET}: ${_TARGET}
.endif
.endfor
${_TARGET}_ARG=		${${_TARGET}_ARGS:[0]}
.endif
.endfor

ensure-stable:
	@if ! git show-ref --verify --quiet refs/heads/stable/${CORE_ABI}; then \
		git update-ref refs/heads/stable/${CORE_ABI} refs/remotes/origin/stable/${CORE_ABI}; \
		git config branch.stable/${CORE_ABI}.merge refs/heads/stable/${CORE_ABI}; \
		git config branch.stable/${CORE_ABI}.remote origin; \
	fi

diff: ensure-stable
	@git diff --stat -p stable/${CORE_ABI} ${.CURDIR}/${diff_ARGS:[1]}

mfc: ensure-stable clean-mfcdir
.for MFC in ${mfc_ARGS}
.if exists(${MFC})
	@cp -r ${MFC} ${MFCDIR}
	@git checkout stable/${CORE_ABI}
	@rm -rf ${MFC}
	@mv ${MFCDIR}/$$(basename ${MFC}) ${MFC}
	@git add -f .
	@if ! git diff --quiet HEAD; then \
		git commit -m "${MFC}: sync with ${CORE_DEVEL}"; \
	fi
.else
	@git checkout stable/${CORE_ABI}
	@if ! git cherry-pick -x ${MFC}; then \
		git cherry-pick --abort; \
	fi
.endif
	@git checkout ${CORE_DEVEL}
.endfor

stable:
	@git checkout ${CORE_STABLE}

devel ${CORE_DEVEL}:
	@git checkout ${CORE_DEVEL}

log: ensure-stable
	@git log --stat -p ${CORE_STABLE}

push:
	@git checkout ${CORE_STABLE}
	@git push
	@git checkout ${CORE_DEVEL}

migrate:
	@src/opnsense/mvc/script/run_migrations.php

validate:
	@src/opnsense/mvc/script/run_validations.php

test: debug
	@if [ "$$(${VERSIONBIN} -v)" != "${CORE_PKGVERSION}" ]; then \
		echo "Installed version does not match, expected ${CORE_PKGVERSION}"; \
		exit 1; \
	fi
	@cd ${.CURDIR}/src/opnsense/mvc/tests && \
	    phpunit --configuration PHPunit.xml || true; \
	    rm -f .phpunit.result.cache

checkout:
	@${GIT} reset -q ${.CURDIR}/src && \
	    ${GIT} checkout -f ${.CURDIR}/src && \
	    ${GIT} clean -xdqf ${.CURDIR}/src

clean-pkgdir:
	@rm -rf ${PKGDIR}
	@mkdir -p ${PKGDIR}

clean-mfcdir:
	@rm -rf ${MFCDIR}
	@mkdir -p ${MFCDIR}

clean-wrksrc:
	@rm -rf ${WRKSRC}
	@mkdir -p ${WRKSRC}

clean: clean-pkgdir clean-wrksrc clean-mfcdir

.PHONY: license plist

