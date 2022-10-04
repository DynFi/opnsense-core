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


<link rel="stylesheet" type="text/css" href="{{ cache_safe(theme_file_or_default('/css/nv.d3.css', ui_theme|default('dynfi'))) }}" />
<script src="{{ cache_safe('/ui/js/d3.min.js') }}"></script>
<script src="{{ cache_safe('/ui/js/nv.d3.min.js') }}"></script>

<style>
#rpz svg {
    height: 300px;
}
</style>

<script>
var chart;
var charts_per_c = {};

nv.addGraph(function() {
    chart = nv.models.pieChart()
        .x(function(d) { return d.label })
        .y(function(d) { return d.value })
        .showLabels(true);
    return chart;
});


function _createPCGraph(category, data) {
    nv.addGraph(function() {
        charts_per_c[category] = nv.models.pieChart()
            .x(function(d) { return d.label })
            .y(function(d) { return d.value })
            .showLabels(true);
        d3.select("#chart-" + category + " svg").datum(data).transition().duration(0).call(charts_per_c[category]);
        return charts_per_c[category];
    });
}

function buildPerCategoryGraphs(data) {
    console.dir(data);
    for (var category in data) {
        var cname = category.charAt(0).toUpperCase() + category.slice(1);
        $('#percategory').append('<section class="col-xs-12 col-md-6 col-lg-4" style="padding-top: 0"><div class="panel panel-default">'
            + '<div class="panel-heading"><h3 class="panel-title">' + cname + '</h3></div>'
            + '<div class="panel-body"><div id="chart-' + category + '"><svg></svg></div></div></div></section>');
        _createPCGraph(category, data[category]);
    }
}


function getChartData() {
    $('#chart_title').hide();
    $('#loading').show();
    ajaxGet("/api/rpz/chart/getCategoriesChartData", {}, function (data, status) {
        $('#loading').hide();
        $('#chart_title').show();
        if (status == "success") {
            d3.select("#chart svg").datum(data['categories']).transition().duration(0).call(chart);
            buildPerCategoryGraphs(data['per_category']);
        } else {
            alert("Error while fetching data: " + status);
        }
    });
}

$(document).ready(function() {
    getChartData();
});
</script>

<div class="tab-content">
    <div id="rpz">
        <div class="row">
            <section class="col-xs-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <span id="loading">
                                <i id="loading" class="fa fa-spinner fa-spin"></i>
                                <b>{{ lang._('Please wait while loading data...') }}</b>
                            </span>
                            <span id="chart_title">Categories chart</span>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div id="chart">
                            <svg></svg>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="row" id="percategory">
        </div>
    </div>
</div>
