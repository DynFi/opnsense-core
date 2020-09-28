{% if helpers.exists('OPNsense.DFConAg.settings.enabled') and OPNsense.DFConAg.settings.enabled == '1' %}
dfconag_enable="YES"
{% else %}
dfconag_enable="NO"
{% endif %}
