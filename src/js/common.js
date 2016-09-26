/* 
 * The MIT License
 *
 * Copyright 2016 Jeppe Boysen Vennekilde.
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

$( document ).ready(function() {
    if (typeof jQuery.ui !== 'undefined'){
        $( ".resizeable" ).resizable();
    }
    
    //Resize content if inside iframe, when switching tab
    $('.mdl-tabs__tab').on('click',function(){
        setTimeout(function(){
            adjustHeight();
        }, 50);
     });
})

function generateAjaxPostData(){
    var data = {};
    var sessions = getParameterByName("ls-sessions");
    if(sessions !== undefined){
        data["ls-sessions"] = sessions;
    }
    
    return data;
}
function adjustHeight(){
    if(parent.AdjustIframeHeight !== undefined){
        parent.AdjustIframeHeight(document.getElementById("gw2i-container").scrollHeight);
    }
}
function getParameterByName(name, url) {
    if (!url)
        url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
    if (!results)
        return null;
    if (!results[2])
        return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function setCookie(cname, cvalue, exseconds) {
    var d = new Date();
    d.setTime(d.getTime() + (exseconds*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

function decodeEntities(encodedString) {
    var textArea = document.createElement('textarea');
    textArea.innerHTML = encodedString;
    return textArea.value;
}

function scrollToBottom(container){
    $(container).scrollTop($(container)[0].scrollHeight);
}