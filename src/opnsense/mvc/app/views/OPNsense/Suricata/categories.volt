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
                    <input name="autoflowbits" type="checkbox" value="1" />
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
                        <input name="ipspolicyenable" type="checkbox" value="1" />
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
                            <option value="connectivity">Connectivity</option>
                            <option value="balanced">Balanced</option>
                            <option value="security">Security</option>
                            <option value="maxdetect">Maximum Detection</option>
                        </select>
                        <div class="hidden" data-for="help_for_ipspolicy">
                            Connectivity blocks most major threats with few or no false positives. Balanced is a good starter policy. It is speedy, has good base coverage level, and covers most threats of the day. It includes all rules in Connectivity. Security is a stringent policy. It contains everything in the first two plus policy-type rules such as Flash in an Excel file. Maximum Detection encompasses vulnerabilities from 2005 or later with a CVSS score of at least 7.5 along with critical malware and exploit kit rules. The Maximum Detection policy favors detection over rated throughput. In some situations this policy can and will cause significant throughput reductions.
                        </div>
                    </td>
                </tr>
            {% endif %}

        </tbody>
    </table>
</form>
