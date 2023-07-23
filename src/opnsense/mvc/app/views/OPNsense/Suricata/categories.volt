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


<form method="post">
    <input type="hidden" name="{{ csrf_tokenKey }}" value="{{ csrf_token }}" autocomplete="new-password" />
    <table class="table table-striped opnsense_standard_table_form">
        <tbody>

            <tr><td style="width: 22%"><strong>Automatic flowbit resolution</strong></td><td style="width: 78%"></td></tr>
            <tr>
                <td>
                    <a id="help_for_autoflowbits" href="#" class="showhelp">
                        <i class="fa fa-info-circle"></i>
                    </a>
                    Resolve Flowbits
                </td>
                <td>
                    <input name="autoflowbits" type="checkbox" value="1" {% if suricatacfg['autoflowbits'] == '1' %}checked="checked"{% endif %} />
                    <div class="hidden" data-for="help_for_autoflowbits">
                        Suricata will examine the enabled rules in your chosen rule categories for checked flowbits. Any rules that set these dependent flowbits will be automatically enabled and added to the list of files in the interface rules directory.
                    </div>
                </td>
            </tr>

            {% if pconfig['enablevrtrules'] == '1' %}
                <tr><td style="width: 22%"><strong>Snort IPS Policy selection</strong></td><td style="width: 78%"></td></tr>
                <tr>
                    <td>
                        <a id="help_for_ipspolicyenable" href="#" class="showhelp">
                            <i class="fa fa-info-circle"></i>
                        </a>
                        Use IPS Policy
                    </td>
                    <td>
                        <input name="ipspolicyenable" type="checkbox" value="1" {% if suricatacfg['ipspolicyenable'] == '1' %}checked="checked"{% endif %} />
                        Use rules from one of three pre-defined Snort IPS policies
                        <div class="hidden" data-for="help_for_ipspolicyenable">
                            <span class="text-danger"><strong>Note:</strong></span> You must be using the Snort rules to use this option<br />
                            Selecting this option disables manual selection of Snort rules categories in the list below, although Emerging Threats categories may still be selected if enabled on the Global Settings tab. These will be added to the pre-defined Snort IPS policy rules from the Snort rules set.
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>
                        <a id="help_for_ipspolicy" href="#" class="showhelp">
                            <i class="fa fa-info-circle"></i>
                        </a>
                        IPS Policy Selection
                    </td>
                    <td>
                        <select name="ipspolicy" class="selectpicker">
                            <option {% if suricatacfg['ipspolicy'] == 'connectivity' %}selected{% endif %} value="connectivity">Connectivity</option>
                            <option {% if suricatacfg['ipspolicy'] == 'balanced' %}selected{% endif %} value="balanced">Balanced</option>
                            <option {% if suricatacfg['ipspolicy'] == 'security' %}selected{% endif %} value="security">Security</option>
                            <option {% if suricatacfg['ipspolicy'] == 'maxdetect' %}selected{% endif %} value="maxdetect">Maximum Detection</option>
                        </select>
                        <div class="hidden" data-for="help_for_ipspolicy">
                            Connectivity blocks most major threats with few or no false positives. Balanced is a good starter policy. It is speedy, has good base coverage level, and covers most threats of the day. It includes all rules in Connectivity. Security is a stringent policy. It contains everything in the first two plus policy-type rules such as Flash in an Excel file. Maximum Detection encompasses vulnerabilities from 2005 or later with a CVSS score of at least 7.5 along with critical malware and exploit kit rules. The Maximum Detection policy favors detection over rated throughput. In some situations this policy can and will cause significant throughput reductions.
                        </div>
                    </td>
                </tr>
            {% endif %}

        </tbody>
    <table>
    <table class="table table-striped opnsense_standard_table_form">
        <tbody>


            <tr>
                <td><strong>Rulesets Suricata will load at startup</strong></td>
            </tr>
            <tr>
                <td style="width: 50%">
                    <i class="fa fa-adn text-success"></i>&nbsp;- Category is auto-enabled by SID Mgmt conf files<br/>
                    <i class="fa fa-adn text-danger"></i>&nbsp;- Category is auto-disabled by SID Mgmt conf files
                </td>
                <td style="width: 50%; text-align: right; vertical-align: bottom">
                    <button type="submit" id="selectall" name="selectall" class="btn btn-info btn-sm" title="Add all categories to enforcing rules">
                        Select All
                    </button>
                    <button type="submit" id="unselectall" name="unselectall" class="btn btn-warning btn-sm" title="Remove all categories from enforcing rules">
                        Unselect All
                    </button>
                </td>
            </tr>

            {% for rule in com_rules %}
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="toenable[]" value="{{ rule['file'] }}" {% if rule['enabled'] %}checked="checked"{% endif %} />
                        {{ rule['name'] }}
                    </td>
                </tr>
            {% endfor %}

            <tr>
                <td style="width: 50%">ET Open Rules</td>
                <td style="width: 50%">Snort Text Rules</td>
            </tr>

            {% for rule in oth_rules %}
                <tr>
                    <td style="width: 50%">
                        {% if rule['emerging'] %}
                            <input type="checkbox" name="toenable[]" value="{{ rule['emerging']['file'] }}" {% if rule['emerging']['enabled'] %}checked="checked"{% endif %} />
                            {{ rule['emerging']['name'] }}
                        {% endif %}
                    </td>
                    <td style="width: 50%">
                        {% if rule['snort'] %}
                            <input type="checkbox" name="toenable[]" value="{{ rule['snort']['file'] }}" {% if rule['snort']['enabled'] %}checked="checked"{% endif %} />
                            {{ rule['snort']['name'] }}
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}

            <tr>
                <td></td>
                <td>
                    <input name="Submit" type="submit" class="btn btn-primary" value="Save" />
                </td>
            </tr>
        </tbody>
    </table>
</form>
