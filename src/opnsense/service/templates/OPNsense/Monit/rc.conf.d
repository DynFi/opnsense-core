# DO NOT EDIT THIS FILE -- OPNsense auto-generated file
{% if helpers.exists('OPNsense.monit.general.enabled') and OPNsense.monit.general.enabled|default("0") == "1" %}
monit_setup="/usr/local/opnsense/scripts/OPNsense/Monit/setup.sh"
monit_enable="YES"
{% else %}
monit_enable="NO"
{% endif %}
