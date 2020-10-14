{% if helpers.exists('OPNsense.DFConAg.settings.enabled') and OPNsense.DFConAg.settings.enabled == '1' %}
dfconag_enable="YES"
dfm_ssh_port="{{ OPNsense.DFConAg.settings.dfmSshPort }}"
dfm_host="{{ OPNsense.DFConAg.settings.dfmHost }}"
remote_ssh_port="{% if helpers.exists('system.ssh.port') and system.ssh.port != '' %}{{ system.ssh.port }}{% else %}22{% endif %}"
remote_dv_port="{% if helpers.exists('system.webgui.port') and system.webgui.port != '' %}{{ system.webgui.port }}{% else %}{% if system.webgui.protocol == 'https' %}443{% else %}80{% endif %}{% endif %}"
main_tunnel_port="{{ OPNsense.DFConAg.settings.mainTunnelPort }}"
dv_tunnel_port="{{ OPNsense.DFConAg.settings.dvTunnelPort }}"
{% else %}
dfconag_enable="NO"
{% endif %}
