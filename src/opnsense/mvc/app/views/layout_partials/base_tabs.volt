{#

OPNsense® is Copyright © 2014 – 2015 by Deciso B.V.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

1.  Redistributions of source code must retain the above copyright notice,
    this list of conditions and the following disclaimer.

2.  Redistributions in binary form must reproduce the above copyright notice,
    this list of conditions and the following disclaimer in the documentation
    and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED “AS IS” AND ANY EXPRESS OR IMPLIED WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

#}

<ul class="nav nav-tabs" role="tablist"  id="maintabs">
{% for tab in tabs|default([]) %}
    {% if tab['subtabs']|default(false) %}
        {# Tab with dropdown #}

        {# Find active subtab #}
            {% set active_subtab="" %}
            {% for subtab in tab['subtabs']|default({}) %}
                {% if subtab[0]==activetab|default("") %}
                    {% set active_subtab=subtab[0] %}
                {% endif %}
            {% endfor %}

        <li role="presentation" class="dropdown {% if activetab|default("") == active_subtab %}active{% endif %}">
            <a data-toggle="dropdown" href="#" class="dropdown-toggle pull-right visible-lg-inline-block visible-md-inline-block visible-xs-inline-block visible-sm-inline-block" role="button" style="border-left: 1px dashed lightgray;">
                <b><span class="caret"></span></b>
            </a>
            <a data-toggle="tab" href="#subtab_{{tab['subtabs'][0][0]}}" class="visible-lg-inline-block visible-md-inline-block visible-xs-inline-block visible-sm-inline-block" style="border-right:0px;"><b>{{tab[1]}}</b></a>
            <ul class="dropdown-menu" role="menu">
                {% for subtab in tab['subtabs']|default({})%}
                <li class="{% if activetab|default("") == subtab[0] %}active{% endif %}"><a data-toggle="tab" href="#subtab_{{subtab[0]}}"><i class="fa fa-check-square"></i> {{subtab[1]}}</a></li>
                {% endfor %}
            </ul>
        </li>
    {% else %}
        {# Standard Tab #}
        <li {% if activetab|default("") == tab[0] %} class="active" {% endif %}>
                <a data-toggle="tab" href="#tab_{{tab[0]}}">
                    <b>{{tab[1]}}</b>
                </a>
        </li>
    {% endif %}
{% endfor %}
</ul>

<div class="content-box tab-content">
    {% for tab in tabs|default([]) %}
        {% if tab['subtabs']|default(false) %}
            {# Tab with dropdown #}
            {% for subtab in tab['subtabs']|default({})%}
                <div id="subtab_{{subtab[0]}}" class="tab-pane fade{% if activetab|default("") == subtab[0] %} in active {% endif %}">
                    {{ partial("layout_partials/base_form",['fields':subtab[2],'id':'frm_'~subtab[0],'data_title':subtab[1],'apply_btn_id':'save_'~subtab[0]])}}
                </div>
            {% endfor %}
        {% endif %}
        {% if tab['subtabs']|default(false)==false %}
            <div id="tab_{{tab[0]}}" class="tab-pane fade{% if activetab|default("") == tab[0] %} in active {% endif %}">
                {{ partial("layout_partials/base_form",['fields':tab[2],'id':'frm_'~tab[0],'apply_btn_id':'save_'~tab[0]])}}
            </div>
        {% endif %}
    {% endfor %}
</div>
