{#
 # Copyright (C) 2022 DynFi
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
.content-box-head {
    color: #FFF;
    background: #b4b7b9;
    font-weight: bold;
    padding: 10px 15px;
    font-size: 120%;
}

.content-box {
    margin-bottom: 20px;
    padding-bottom: 10px;
}
</style>

<div class="content-box">
    <header class="content-box-head container-fluid">
        {{ lang._('Installed rule set MD5 signatures') }}
    </header>
    <div class="content-box-main">
        <div class="table-responsive">
            <div  class="col-sm-12">
                <table class="table table-condensed table-hover table-striped table-responsive">
                    <thead>
                        <tr>
                            <th>{{ lang._('Rule Set Name/Publisher') }}</th>
                            <th>{{ lang._('MD5 Signature Hash') }}</th>
                            <th>{{ lang._('MD5 Signature Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for rt in rulesTable %}
                            <tr>
                                <td>{{ rt['name'] }}</td>
                                <td>{{ rt['sighash'] }}</td>
                                <td>{{ rt['sigdate'] }}</td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="content-box">
    <header class="content-box-head container-fluid">
        {{ lang._('Update your rule set') }}
    </header>
    <div class="content-box-main">
        <div class="table-responsive">
            <div class="col-sm-12">
                <table class="table table-condensed table-hover table-striped table-responsive">
                    <tbody>
                        <tr><th>{{ lang._('Last update') }}</th><td></td></tr>
                        <tr><th>{{ lang._('Result') }}</th><td></td></tr>
                    </tbody>
                </table>
                <div style="margin-top: 10px">
                    <button class="btn btn-primary" disabled><i class="fa fa-check"></i> {{ lang._('Update') }}</button>
                    <button class="btn btn-warning" disabled><i class="fa fa-download"></i>{{ lang._('Force') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

