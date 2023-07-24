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

<script>
$(document).ready(function() {
    $('#selectbox').change(function() {
        var ruleset = $('#selectbox').find('option:selected').val();
        if (ruleset) {
            $('#openruleset').val(ruleset);
            $('#iform').submit();
        }
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
