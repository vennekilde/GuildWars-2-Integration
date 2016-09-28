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

$( document ).ready(function(){
    //initialize ajax forms
    $('.default-admin-form').submit(function (ev) {
        form = $(this);
        ev.preventDefault();

        var data = form.serialize();
        var formName = form.attr("name");
        if(data){
            data += "&form="+formName;
        } else {
            data = "form="+formName;
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
                if(json["data"] !== false){
                    var data = json["data"];
                    for (var key in data) {
                        html += "<h5 style='text-transform: uppercase'>"+key.replace(/\-/g," ")+"</h5>";
                        if(typeof data[key] === 'object') {
                            html += JSON.stringify(data[key], null, 4);
                        } else {
                            var str = data[key].replace(/\</g,"&lt;");
                            html = str.replace(/\>/g,"&gt;");
                        }
                    }
                } else {
                    html = "No results found";
                }
                div.html(html);
                div.show();
                adjustHeight();
            },
            error: function(response){
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
        if(data){
            data += "&form="+formName;
        } else {
            data = "form="+formName;
        }
        console.log(data);
        startSpinner(form);
        retrieveLog(form, data);
    });
    
    //Auto resize if within an IFrame
    $(".detailed-log").bind('resize', function(){
        adjustHeight();
     }); 
     
    //Retrieve the latest log
    $("#fetch-latest-log-btn").click();
});

function retrieveLog(form, data){
    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: data,
        success: function (response) {
            stopSpinner(form);
            var json = JSON.parse(response)
            var div = form.find(".response-div");
            var html = "";
            if(json["data"]["log"] !== false){
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
        error: function(response){
            console.log("error");
            console.log(response);
            stopSpinner(form);
        }
    });
}

function startSpinner(form){
    form.find(".spinner-button").css('display', 'inline-block');
    form.find(".mdl-button").prop("disabled",true);
}
function stopSpinner(form){
    form.find(".spinner-button").hide();
    form.find(".mdl-button").prop("disabled",false);
}