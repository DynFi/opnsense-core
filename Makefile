PKG!=		which pkg || echo true
GIT!=		which git || echo true
PAGER?=		less

all:
	@cat ${.CURDIR}/README.md | ${PAGER}

force:

WRKDIR?=${.CURDIR}/work
WRKSRC=	${WRKDIR}/src
PKGDIR=	${WRKDIR}/pkg

mount: force
	@if [ ! -f ${WRKDIR}/.mount_done ]; then \
	    echo -n "Enabling core.git live mount..."; \
	    ${.CURDIR}/scripts/version.sh > \
	        ${.CURDIR}/src/opnsense/version/opnsense; \
	    mount_unionfs ${.CURDIR}/src /usr/local; \
	    touch ${WRKDIR}/.mount_done; \
	    echo "done"; \
	    service configd restart; \
	fi

umount: force
	@if [ -f ${WRKDIR}/.mount_done ]; then \
	    echo -n "Disabling core.git live mount..."; \
	    umount -f "<above>:${.CURDIR}/src"; \
	    rm ${.CURDIR}/src/opnsense/version/opnsense; \
	    rm ${WRKDIR}/.mount_done; \
	    echo "done"; \
	    service configd restart; \
	fi

.if ${GIT} != true
CORE_COMMIT!=	${.CURDIR}/scripts/version.sh
CORE_VERSION=	${CORE_COMMIT:C/-.*$//1}
CORE_HASH=	${CORE_COMMIT:C/^.*-//1}
.endif

.if "${FLAVOUR}" == OpenSSL || "${FLAVOUR}" == ""
CORE_REPOSITORY?=	latest
.elif "${FLAVOUR}" == LibreSSL
CORE_REPOSITORY?=	libressl
.else
CORE_REPOSITORY?=	${FLAVOUR}
.endif

CORE_PACKAGESITE?=	http://pkg.opnsense.org

CORE_NAME?=		opnsense-devel
CORE_FAMILY?=		development
CORE_ORIGIN?=		opnsense/${CORE_NAME}
CORE_COMMENT?=		OPNsense ${CORE_FAMILY} package
CORE_MAINTAINER?=	franco@opnsense.org
CORE_WWW?=		https://opnsense.org/
CORE_MESSAGE?=		ACME delivery for the crafty coyote!
CORE_DEPENDS?=		apinger \
			beep \
			bind910 \
			bsdinstaller \
			bsnmp-regex \
			bsnmp-ucd \
			ca_root_nss \
			choparp \
			cpustats \
			dhcp6 \
			dhcpleases \
			dnsmasq \
			expiretable \
			filterdns \
			filterlog \
			ifinfo \
			flowd \
			igmpproxy \
			isc-dhcp43-client \
			isc-dhcp43-relay \
			isc-dhcp43-server \
			lighttpd \
			minicron \
			miniupnpd \
			mpd5 \
			ngattach \
			ntp \
			openssh-portable \
			openvpn \
			opnsense-update \
			p7zip \
			pecl-radius \
			pftop \
			phalcon \
			php-pfSense \
			php-suhosin \
			php56 \
			php56-bcmath \
			php56-ctype \
			php56-curl \
			php56-dom \
			php56-filter \
			php56-gettext \
			php56-hash \
			php56-json \
			php56-ldap \
			php56-mcrypt \
			php56-openssl \
			php56-pdo \
			php56-pdo_sqlite \
			php56-session \
			php56-simplexml \
			php56-sockets \
			php56-sqlite3 \
			php56-xml \
			php56-zlib \
			py27-Jinja2 \
			py27-netaddr \
			py27-requests \
			py27-sqlite3 \
			py27-ujson \
			python27 \
			radvd \
			rate \
			relayd \
			rrdtool12 \
			samplicator \
			squid \
			sshlockout_pf \
			strongswan \
			sudo \
			suricata \
			syslogd \
			unbound \
			wol \
			zip

manifest: force
	@echo "name: \"${CORE_NAME}\""
	@echo "version: \"${CORE_VERSION}\""
	@echo "origin: \"${CORE_ORIGIN}\""
	@echo "comment: \"${CORE_COMMENT}\""
	@echo "desc: \"${CORE_HASH}\""
	@echo "maintainer: \"${CORE_MAINTAINER}\""
	@echo "www: \"${CORE_WWW}\""
	@echo "message: \"${CORE_MESSAGE}\""
	@echo "categories: [ \"sysutils\", \"www\" ]"
	@echo "licenselogic: \"single\""
	@echo "licenses: [ \"BSD2CLAUSE\" ]"
	@echo "prefix: /usr/local"
	@echo "deps: {"
	@for CORE_DEPEND in ${CORE_DEPENDS}; do \
		if ! ${PKG} query '  %n: { version: "%v", origin: "%o" }' \
		    $${CORE_DEPEND}; then \
			echo ">>> Missing dependency: $${CORE_DEPEND}" >&2; \
			exit 1; \
		fi; \
	done
	@echo "}"

name: force
	@echo ${CORE_NAME}

depends: force
	@echo ${CORE_DEPENDS}

scripts: force
	@mkdir -p ${DESTDIR}
	@cp -v -- +PRE_DEINSTALL +POST_INSTALL ${DESTDIR}
	@sed -i '' -e "s/%%CORE_COMMIT%%/${CORE_COMMIT}/g" \
	    ${DESTDIR}/+POST_INSTALL

install: force
	@${MAKE} -C ${.CURDIR}/contrib install DESTDIR=${DESTDIR}
	@${MAKE} -C ${.CURDIR}/lang install DESTDIR=${DESTDIR}
	@${MAKE} -C ${.CURDIR}/src install DESTDIR=${DESTDIR} \
	    CORE_PACKAGESITE=${CORE_PACKAGESITE} \
	    CORE_REPOSITORY=${CORE_REPOSITORY}

bootstrap: force
	@${MAKE} -C ${.CURDIR}/src install_bootstrap DESTDIR=${DESTDIR} \
	    NO_SAMPLE=please CORE_PACKAGESITE=${CORE_PACKAGESITE} \
	    CORE_REPOSITORY=${CORE_REPOSITORY}

plist: force
	@${MAKE} -C ${.CURDIR}/contrib plist
	@${MAKE} -C ${.CURDIR}/lang plist
	@${MAKE} -C ${.CURDIR}/src plist

package-keywords: force
	@if [ ! -f /usr/ports/Keywords/sample.ucl ]; then \
		mkdir -p /usr/ports/Keywords; \
		cd /usr/ports/Keywords; \
		fetch https://raw.githubusercontent.com/opnsense/ports/master/Keywords/sample.ucl; \
	fi
	@echo ">>> Installed /usr/ports/Keywords/sample.ucl"

package: force
	@if [ -f ${WRKDIR}/.mount_done ]; then \
		echo ">>> Cannot continue with live mount.  Please run 'make umount'." >&2; \
		exit 1; \
	fi
	@if [ ! -f /usr/ports/Keywords/sample.ucl ]; then \
		echo ">>> Missing required file(s).  Please run 'make package-keywords'" >&2; \
		exit 1; \
	fi
	@${PKG} info gettext-tools > /dev/null
	@${PKG} info git > /dev/null
	@rm -rf ${WRKSRC} ${PKGDIR}
	@${MAKE} DESTDIR=${WRKSRC} FLAVOUR=${FLAVOUR} install
	@${MAKE} DESTDIR=${WRKSRC} scripts
	@${MAKE} DESTDIR=${WRKSRC} manifest > ${WRKSRC}/+MANIFEST
	@${MAKE} DESTDIR=${WRKSRC} plist > ${WRKSRC}/plist
	@${PKG} create -v -m ${WRKSRC} -r ${WRKSRC} \
	    -p ${WRKSRC}/plist -o ${PKGDIR}
	@echo -n "Sucessfully built "
	@cd ${PKGDIR}; find . -name "*.txz" | cut -c3-

upgrade: package
	${PKG} delete -y ${CORE_NAME}
	@${PKG} add ${PKGDIR}/*.txz
	@/usr/local/etc/rc.restart_webgui

lint: force
	find ${.CURDIR}/src ${.CURDIR}/scripts \
	    -name "*.sh" -type f -print0 | xargs -0 -n1 sh -n
	find ${.CURDIR}/src ${.CURDIR}/src \
	    -name "*.xml" -type f -print0 | xargs -0 -n1 xmllint --noout
	find ${.CURDIR}/src ${.CURDIR}/lang/dynamic/helpers \
	    ! -name "*.xml" ! -name "*.xml.sample" ! -name "*.eot" \
	    ! -name "*.svg" ! -name "*.woff" ! -name "*.woff2" \
	    ! -name "*.otf" ! -name "*.png" ! -name "*.js" \
	    ! -name "*.scss" ! -name "*.py" ! -name "*.ttf" \
	    ! -name "*.tgz" ! -name "*.xml.dist" \
	    -type f -print0 | xargs -0 -n1 php -l

sweep: force
	find ${.CURDIR}/src ! -name "*.min.*" ! -name "*.svg" \
	    ! -name "*.map" ! -name "*.ser" -type f -print0 | \
	    xargs -0 -n1 scripts/cleanfile
	find ${.CURDIR}/lang -type f -print0 | \
	    xargs -0 -n1 scripts/cleanfile
	find ${.CURDIR}/scripts -type f -print0 | \
	    xargs -0 -n1 scripts/cleanfile

style: force
	@(phpcs --tab-width=4 --standard=PSR2 ${.CURDIR}/src/opnsense/mvc \
	    || true) > ${.CURDIR}/.style.out
	@echo -n "Total number of style warnings: "
	@grep '| WARNING' ${.CURDIR}/.style.out | wc -l
	@echo -n "Total number of style errors:   "
	@grep '| ERROR' ${.CURDIR}/.style.out | wc -l
	@cat ${.CURDIR}/.style.out
	@rm ${.CURDIR}/.style.out

stylefix: force
	phpcbf --standard=PSR2 ${.CURDIR}/src/opnsense/mvc || true

setup: force
	${.CURDIR}/src/etc/rc.php_ini_setup

health: force
	# check test script output and advertise a failure...
	[ "`${.CURDIR}/src/etc/rc.php_test_run`" == "FCGI-PASSED PASSED" ]

clean:
	${GIT} reset --hard HEAD && ${GIT} clean -xdqf .

.PHONY: force
