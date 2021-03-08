{% if helpers.exists('OPNsense.DFConAg.settings.enabled') and OPNsense.DFConAg.settings.enabled == '1' %}
dfconag_enable="YES"
dfm_ssh_port="{{ OPNsense.DFConAg.settings.dfmSshPort }}"
dfm_host="{{ OPNsense.DFConAg.settings.dfmHost }}"
remote_ssh_port="{{ OPNsense.DFConAg.settings.localSshPort }}"
remote_dv_port="{{ OPNsense.DFConAg.settings.localDvPort }}"
main_tunnel_port="{{ OPNsense.DFConAg.settings.mainTunnelPort }}"
dv_tunnel_port="{{ OPNsense.DFConAg.settings.dvTunnelPort }}"
{% else %}
dfconag_enable="NO"
{% endif %}
