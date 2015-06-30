{% set addFlags=[] %}
{% if helpers.exists('OPNsense.IDS.general') and OPNsense.IDS.general.enabled|default("0") == "1" %}
suricata_enable="YES"
{% for intfName in OPNsense.IDS.general.interfaces.split(',') %}
{%   if loop.index == 1 %}
{# enable first interface #}
suricata_interface="{{helpers.getNodeByTag('interfaces.'+intfName).if}}"
{%   else %}
{#   store additional interfaces to addFlags #}
{%      do addFlags.append(helpers.getNodeByTag('interfaces.'+intfName).if) %}
{%   endif %}
{% endfor %}
{#   append additional interfaces #}
suricata_flags="-D {%
   for intf in addFlags
%} -i {{ intf }}  {% endfor
%} "
{% else %}
suricata_enable="NO"
{% endif %}
