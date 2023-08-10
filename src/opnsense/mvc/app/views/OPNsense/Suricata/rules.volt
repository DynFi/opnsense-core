{#
 # Copyright (C) 2023 DynFi
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without modification,
 # are permitted provided that the following conditions are met:
 #
 # 1. Redistributions of source code must retain the above copyright notice,
 #    this list of conditions and the following disclaimer.
 #
 # 2. Redistributions in binary form must reproduce the above copyright notice,
 #    this list of conditions and the following disclaimer in the documentation
 #    and/or other materials provided with the distribution.
 #
 # THIS SOFTWARE IS PROVIDED “AS IS” AND ANY EXPRESS OR IMPLIED WARRANTIES,
 # INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 # AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 # AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 # OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 # SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 # INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 # CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 # ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 # POSSIBILITY OF SUCH DAMAGE.
 #}

<style>
#grid-sidrules-header { display: none }
#grid-sidrules-footer { display: none }

#grid-sidrules td:last-child { text-overflow: unset; white-space: unset }
</style>

<script>
$(document).ready(function() {
    $('#selectbox').change(function() {
        var ruleset = $('#selectbox').find('option:selected').val();
        if (ruleset) {
            $('#openruleset').val(ruleset);
            $('#iform').submit();
        }
    });

    $("#grid-sidrules").UIBootgrid({
        search: '/api/suricata/sidrules/searchItem/{{ uuid }}/{{ currentruleset }}',
        selection: false,
        rowCount: -1
    });
});
</script>

<form method="post" id="iform" action="/ui/suricata/configure/iface/{{ uuid }}#rules">
    <input type="hidden" name="{{ csrf_tokenKey }}" value="{{ csrf_token }}" autocomplete="new-password" />
    <input type="hidden" name="openruleset" id="openruleset" value="{{ currentruleset }}"/>
</form>

<table class="table table-striped opnsense_standard_table_form">
    <tbody>
        <tr><td colspan="2"><strong>Available Rule Categories</strong></td></tr>
        <tr>
            <td style="width: 22%">
                Category
            </td>
            <td style="width: 78%">
                <select name="selectbox" id="selectbox" class="selectpicker">
                    {% for c, cn in categories %}
                        <option {% if currentruleset == c %}selected{% endif %} value="{{ c }}">{{ cn }}</option>
                    {% endfor %}
                </select>
            </td>
        </tr>
    </tbody>
</table>

<table class="table table-striped opnsense_standard_table_form">
    <tbody>
        <tr><td colspan="2"><strong>Rule Signature ID (SID) Enable/Disable Overrides</strong></td></tr>
        <tr>
            <td><b>Legend:</b></td>
            <td><i class="fa fa-check-circle-o text-success"></i> <small>Default Enabled</small></td>
            <td><i class="fa fa-check-circle text-success"></i> <small>Enabled by user</small></td>
            <td><i class="fa fa-adn text-success"></i> <small>Auto-enabled by SID Mgmt</small></td>
            <td><i class="fa fa-adn text-warning"></i> <small>Action/content modified by SID Mgmt</small></td>
            <td><i class="fa fa-exclamation-triangle text-warning"></i> <small>Rule action is alert</small></td>
            <td><i class="fa fa-exclamation-triangle text-success"></i> <small>Rule contains noalert option</small></td>
        </tr>
        <tr>
            <td></td>
            <td><i class="fa fa-times-circle-o text-danger"></i> <small>Default Disabled</small></td>
            <td><i class="fa fa-times-circle text-danger"></i> <small>Disabled by user</small></td>
            <td><i class="fa fa-adn text-danger"></i> <small>Auto-disabled by SID Mgmt</small></td>
            <td></td>
            <td></td>
            {% if suricatacfg['blockoffenders'] == '1' %}
                <td><i class="fa fa-thumbs-down text-danger"></i> <small>Rule action is drop</small></td>
            {% elseif suricatacfg['ipsmode'] == 'inline' %}
                <td><i class="fa fa-hand-stop-o text-warning"></i> <small>Rule action is reject</small></td>
            {% else %}
                <td></td>
            {% endif %}
        </tr>
    </tbody>
</table>

<table id="grid-sidrules" class="table table-condensed table-hover table-striped table-responsive">
    <thead>
        <tr>
            <th data-column-id="id" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
            <th data-column-id="state" data-formatter="html">{{ lang._('State') }}</th>
            <th data-column-id="action" data-formatter="html">{{ lang._('Action') }}</th>
            <th data-column-id="gid" data-formatter="html">{{ lang._('GID') }}</th>
            <th data-column-id="sid" data-formatter="html">{{ lang._('SID') }}</th>
            <th data-column-id="proto" data-formatter="html">{{ lang._('Proto') }}</th>
            <th data-column-id="source" data-formatter="html">{{ lang._('Source') }}</th>
            <th data-column-id="sport" data-formatter="html">{{ lang._('SPort') }}</th>
            <th data-column-id="destination" data-formatter="html">{{ lang._('Destination') }}</th>
            <th data-column-id="dport" data-formatter="html">{{ lang._('DPort') }}</th>
            <th data-column-id="message" data-formatter="html" data-width="20%">{{ lang._('Message') }}</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
