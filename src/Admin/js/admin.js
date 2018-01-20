/* 
 * The MIT License
 *
 * Copyright 2016 venne.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

$(document).ready(function () {
    //Add tag for determining button clicked when submitting a form
    $("form button[type=submit]").click(function () {
        $("button[type=submit]", $(this).parents("form")).removeAttr("clicked");
        $(this).attr("clicked", "true");
    });

    //initialize ajax forms
    $('.default-admin-form').submit(function (ev) {
        form = $(this);
        ev.preventDefault();

        var data = form.serialize();
        var formName = form.attr("name");
        if (data) {
            data += "&form=" + formName;
        } else {
            data = "form=" + formName;
        }

        console.log(data);
        startSpinner(form);
        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: data,
            success: function (response) {
                stopSpinner(form);
                console.log(response);
                console.log("success");
                var json = JSON.parse(response)
                var div = form.find(".response-div");
                var html = "";
                if (json["data"] !== false) {
                    var data = json["data"];
                    for (var key in data) {
                        html += "<h5 style='text-transform: uppercase'>" + key.replace(/\-/g, " ") + "</h5>";
                        if (typeof data[key] === 'object') {
                            html += JSON.stringify(data[key], null, 4);
                        } else if (data[key] === false) {
                            html += "Not available";
                        } else {
                            var str = data[key].replace(/\</g, "&lt;");
                            html += str.replace(/\>/g, "&gt;");
                        }
                    }
                } else {
                    html = "No results found";
                }
                div.html(html);
                div.show();
                adjustHeight();
            },
            error: function (response) {
                console.log("error");
                console.log(response);
                stopSpinner(form);
            }
        });
    });

    $('.log-admin-form').submit(function (ev) {
        form = $(this);
        ev.preventDefault();

        var data = form.serialize();
        var formName = form.attr("name");
        if (data) {
            data += "&form=" + formName;
        } else {
            data = "form=" + formName;
        }
        console.log(data);
        startSpinner(form);
        retrieveLog(form, data);
    });

    $('.statistics-admin-form').each(function () {
        google.charts.load('upcoming', {packages: ['line']});
    });

    $('.statistics-admin-form').submit(function (ev) {
        form = $(this);
        ev.preventDefault();

        var data = form.serialize();
        var formName = form.attr("name");
        if (data) {
            data += "&form=" + formName;
        } else {
            data = "form=" + formName;
        }
        console.log(data);
        startSpinner(form);
        fetchStatisticsChart(form, data);
    });

    $('.verification-event-admin-form').submit(function (ev) {
        form = $(this);
        ev.preventDefault();

        var data = form.serialize();
        var formName = form.attr("name");
        if (data) {
            data += "&form=" + formName;
        } else {
            data = "form=" + formName;
        }

        var submitElement = $("button[type=submit][clicked=true]");
        if (submitElement !== undefined) {
            var name = submitElement.attr("name");
            if (data) {
                data += "&" + name;
            } else {
                data = name;
            }
        }

        console.log(data);
        startSpinner(form);

        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: data,
            success: function (response) {
                stopSpinner(form);
                console.log(response);
                console.log("success");
                $("#verification-events-tbody").empty();
                var json = JSON.parse(response)
                var div = form.find(".response-div");
                var html = "";
                if (json["data"] !== false && json["data"]["events"] !== undefined) {
                    var events = json["data"]["events"];
                    var lastLinkId = null;
                    var useGray = true;
                    for (var key in events) {
                        var linkId = events[key]["link_id"];
                        if (linkId !== lastLinkId) {
                            useGray = !useGray;
                            lastLinkId = linkId;
                        }
                        var entry = '\
                        <tr' + (useGray ? ' class="gray-background"' : "") + '>\
                            <td class="mdl-data-table__cell--non-numeric">' + linkId + '</td>\
                            <td class="mdl-data-table__cell--non-numeric">' + (events[key]["username"] !== null ? events[key]["username"] : "Not linked") + '</td>';

                        serviceNames = json["data"]["services"];
                        $.each(json["data"]["services"], function (serviceId) {
                            var displayName = (events[key]["services"] !== undefined && events[key]["services"][serviceId] !== undefined) ? events[key]["services"][serviceId] : "Not linked";
                            entry += '<td class="mdl-data-table__cell--non-numeric">' + displayName + '</td>';
                        });

                        entry += '<td class="mdl-data-table__cell--non-numeric">' + events[key]["timestamp"] + '</td>\
                            <td class="mdl-data-table__cell--non-numeric">' + parseVerificationEventType(events[key]["event"], events[key]["value"]) + '</td>\
                            <td class="mdl-data-table__cell--non-numeric">' + parseVerificationEventData(events[key]["event"], events[key]["value"]) + '</td>\
                        </tr>';
                        $("#verification-events-tbody").append(entry);
                    }
                } else {
                    html = "No results found";
                }
                div.html(html);
                div.show();
                adjustHeight();
            },
            error: function (response) {
                console.log("error");
                console.log(response);
                stopSpinner(form);
            }
        });
    });

    //Auto resize if within an IFrame
    $(".detailed-log").bind('resize', function () {
        adjustHeight();
    });


    var fetchedOnceTab6 = false;
    $("#tab6-link").on("click", function () {
        //Retrieve charts
        if (!fetchedOnceTab6) {
            $("#update-world-dist-btn").click();
            $("#update-api-stats-btn").click();
            fetchedOnceTab6 = true;
        }
    });
    var fetchedOnceTab5 = false;
    $("#tab5-link").on("click", function () {
        //Retrieve charts
        if (!fetchedOnceTab5) {
            //Retrieve the latest log
            $("#fetch-latest-log-btn").click();
            $("#update-verification-events-btn").click();
            fetchedOnceTab5 = true;
        }
    });
});

function retrieveLog(form, data) {
    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: data,
        success: function (response) {
            stopSpinner(form);
            var json = JSON.parse(response)
            var div = form.find(".response-div");
            var html = "";
            if (json["data"]["log"] !== false) {
                html = json["data"]["log"];
            } else {
                html = "No results found";
            }
            div.html(html);
            div.show();

            //Scroll log to bottom
            scrollToBottom(".detailed-log-container");

            adjustHeight();
        },
        error: function (response) {
            console.log("error");
            console.log(response);
            stopSpinner(form);
        }
    });
}

function startSpinner(form) {
    form.find(".spinner-button").css('display', 'inline-block');
    form.find(".mdl-button").prop("disabled", true);
}
function stopSpinner(form) {
    form.find(".spinner-button").hide();
    form.find(".mdl-button").prop("disabled", false);
}

function fetchStatisticsChart(form, data) {
    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: data,
        success: function (response) {
            console.log(response);
            var json = JSON.parse(response)
            if (json["data"]["chart"] !== undefined) {
                var series = [];
                $.each(json["data"]["chart"], function (i1, value) {
                    var self = this;
                    $.each(value, function (i2, data) {
                        if (i1 === 0) {
                            if (i2 === 0) {
                                return;
                            }
                            series.push({
                                type: "line",
                                name: data,
                                data: [],
                                yAxis: data.startsWith("[T]") ? 1 : 0
                            });
                        } else {
                            if (i2 === 0) {
                                self.date = new Date(data).getTime();
                            } else {
                                series[i2 - 1]["data"].push([
                                    self.date,
                                    data
                                ]);
                            }
                        }
                    });
                });
                console.log(series);
                Highcharts.stockChart(form.find(".chart_div").get(0), {
                    yAxis: [{
                            type: 'logarithmic',
                            minorTickInterval: 0.1,
                            title: {
                                text: 'API Verified Users Per World'
                            },
                            labels: {
                                formatter: function () {
                                    return "";
                                }
                            },
                            height: 800,
                            lineWidth: 2
                        }, {
                            title: {
                                text: 'Temporary Users Per World'
                            },
                            labels: {
                                formatter: function () {
                                    return "";
                                }
                            },
                            top: 750,
                            height: 200,
                            offset: 0,
                            lineWidth: 2
                        }],
                    xAsis: {
                        ordinal: false
                    },
                    tooltip: {
                        crosshairs: true,
                        borderWidth: 0,
                        padding: 1,
                        shadow: false,
                        useHTML: true,
                        style: {
                            padding: 0
                        }
                    },

                    series: series
                });
                stopSpinner(form);

//                var chart = json["data"]["chart"];
//                for(var i = 1; i < chart.length; i++){
//                    chart[i][0] = new Date(chart[i][0]);
//                }
//                console.log(chart);
//                var data = google.visualization.arrayToDataTable(chart);
//
//                var options = {
//                    interpolateNulls: true,
//                    height: 500,
//                    vAxis : {
//                        format: "decimal"
//                    },
//                    hAxis: {
//                      format: 'yy-MM-dd HH:mm:ss'
//                    },
//                    backgroundColor: { fill:'transparent' }
//                };
//
//                if(json["data"]["options"] !== undefined){
//                    options = mergeObjects(options, json["data"]["options"]);
//                }
//                var chart = new google.charts.Line(form.find(".chart_div").get(0));
//                google.visualization.events.addListener(chart, 'ready', function(){
//                    stopSpinner(form);
//                });
//                chart.draw(data, google.charts.Line.convertOptions(options));

                adjustHeight();
            }
        },
        error: function (response) {
            console.log("error");
            console.log(response);
            stopSpinner(form);
        }
    });
}

function parseVerificationEventData(eventType, eventData) {
    var parsedData;
    switch (eventType) {
        case 0:
            var worlds = eventData.split(",");
            fromWorld = (!worlds[0] || 0 === worlds[0].length) ? "" : getWorldNameFromId(worlds[0]);
            toWorld = getWorldNameFromId(worlds[1]);
            parsedData = fromWorld + " -> " + toWorld;
            break;
        case 1:
            if (eventData === 0) {
                parsedData = "Revoked";
            } else if (eventData === 1) {
                parsedData = "Granted";
            } else {
                parsedData = eventData;
            }
            break;
        case 2:
            if (eventData === 0) {
                parsedData = "Refreshed";
            } else if (eventData === 1) {
                parsedData = "Expired";
            } else {
                parsedData = eventData;
            }
            break;
        case 3:
            var split = eventData.split(",");
            parsedData = "Group Id: " + split[1];
            break;
        default:
            parsedData = eventData;
            break;
    }
    return parsedData;
}

function parseVerificationEventType(eventType, eventData) {
    var parsedEvent;
    switch (eventType) {
        case 0:
            parsedEvent = "World Moved";
            break;
        case 1:
            parsedEvent = "Temporary Access";
            break;
        case 2:
            parsedEvent = "GW2 Data";
            break;
        case 3:
            var split = eventData.split(",");
            parsedEvent = serviceNames[split[0]] + " Group " + (split[2] === "1" ? "Added" : "Removed");
            break;
        default:
            parsedEvent = eventType;
            break;
    }
    return parsedEvent;
}