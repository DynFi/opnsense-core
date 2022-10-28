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
    height: 400px;
}

#percategory svg {
    height: 400px;
}

.c-chart {
    background-color: #FFFFFF;
    border: 1px solid #c2c2c2;
}
</style>

<script>
var chart;
var charts_per_c = {};

function _tooltip(d, elem) {
    var table = d3.select(document.createElement("table"));
    var tbodyEnter = table.selectAll("tbody").data([d]).enter().append("tbody");

    var trowEnter = tbodyEnter.selectAll("tr").data(function(p) { return p.series }).enter().append("tr").classed("highlight", function(p) { return p.highlight});

    trowEnter.append("td").classed("legend-color-guide",true).append("div").style("background-color", function(p) { return p.color });

    trowEnter.append("td").classed("key",true).classed("total",function(p) { return !!p.total}).html(function(p, i) { return d.data.label.charAt(0).toUpperCase() + d.data.label.slice(1) });

    trowEnter.append("td").classed("value",true).html(function (p, i) { return d3.format('.2%')(p.percent) });

    trowEnter.selectAll("td").each(function(p) {
        if (p.highlight) {
            var opacityScale = d3.scale.linear().domain([0,1]).range(["#fff",p.color]);
            var opacity = 0.6;
            d3.select(this)
                .style("border-bottom-color", opacityScale(opacity))
                .style("border-top-color", opacityScale(opacity))
            ;
        }
    });

    var html = table.node().outerHTML;
    if (d.footer !== undefined)
        html += "<div class='footer'>" + d.footer + "</div>";
    return html;
}


nv.addGraph(function() {
    chart = nv.models.pieChart()
        .x(function(d) { return d.label })
        .y(function(d) { return d.value })
        .showLabels(true)
        .showTooltipPercent(true)
        .labelType(function(d)  {
            return d.data.label.charAt(0).toUpperCase() + d.data.label.slice(1);
        });
    chart.tooltip.contentGenerator(_tooltip);
    return chart;
});


function _createPCGraph(category, data) {
    nv.addGraph(function() {
        charts_per_c[category] = nv.models.pieChart()
            .x(function(d) { return d.label })
            .y(function(d) { return d.value })
            .showLabels(true)
            .showTooltipPercent(true)
            .labelType(function(d)  {
                var percent = 100.0 * (d.endAngle - d.startAngle) / (2 * Math.PI);
                if (percent > 20)
                    return d.data.label;
                return null;
            });
        charts_per_c[category].tooltip.contentGenerator(_tooltip);
        d3.select("#chart-" + category + " svg").datum(data).transition().duration(0).call(charts_per_c[category]);
        return charts_per_c[category];
    });
}

function buildPerCategoryGraphs(data) {
    for (var category in data) {
        var cname = category.charAt(0).toUpperCase() + category.slice(1);
        $('#percategory').append('<section class="col-xs-12 col-md-6 col-lg-4" style="padding-top: 0"><div class="panel panel-default">'
            + '<div class="panel-heading"><h3 class="panel-title">' + cname + '</h3></div>'
            + '<div class="panel-body"><div class="c-chart" id="chart-' + category + '"><svg></svg></div></div></div></section>');
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
                            <span id="chart_title">Categories chart <span style="float: right">{{ t_from }} - {{ t_to }}</span></span>
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
