<?php

use GW2Integration\Modules\Guilds\GuildsPagesController;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use GW2Integration\Utils\BBCodeUtils;
use GW2Integration\Utils\GW2DataFieldConverter;
use function GuzzleHttp\json_encode;
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

if (!isset($bbcode)) {
    return;
}

include __DIR__ . "/GuildPageController.php"

?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<link rel="stylesheet" href="../../../Public/sceditor/minified/themes/default.min.css" />
<script src="../../../Public/sceditor/minified/jquery.sceditor.bbcode.min.js"></script>

<script>
$(function() {
    // Replace all textarea tags with SCEditor
    $('textarea.bbcode-editor').sceditor({
        plugins: 'bbcode',
        style: '../../../Public/sceditor/minified/jquery.sceditor.default.min.css',
        emoticonsRoot: '../../../Public/sceditor/', 
        resizeWidth: false,
        height: 500
    }).width('100%');
});
</script>

<!-- Simple header with scrollable tabs. -->
<h2 class="guild_page_title" style="margin: 10px;"><?php echo "[" . $guildPage["g_tag"] . "] " . $guildPage["g_name"]; ?></h2>
<div class="mdl-tabs mdl-js-tabs">
    <div style="width: 100%;" class="clearfix">
        <div class="mdl-tabs__tab-bar" style="justify-content: flex-start;-webkit-justify-content: flex-start;-ms-flex-pack: flex-start;">
            <a id="tab1-link" href="#tab1" class="mdl-tabs__tab is-active">Home</a>
            <a id="tab2-link" href="#tab2" class="mdl-tabs__tab">Roster</a>
            <a id="tab3-link" href="#tab3" class="mdl-tabs__tab">Videos</a>
            <?php if($canAdminGuild){ 
                echo '<a id="tab3-link" href="#tab4" class="mdl-tabs__tab">Guild Management</a>';
            } ?>
        </div>

        <div class="vertical-mdl-tabs-panels">
            <div class="mdl-tabs__panel is-active" id="tab1">
                <div class="guild_page clearfix">
                    <div class="guild_page_right_panel">
                        <div class="guild_page_emblem gp" style="background-image: url(<?php echo GuildsPagesController::getGuildEmblemURL($guildPage["g_uuid"]); ?>)"></div>
                            <?php
                            if (isset($guildPage["g_url"])) {
                                $url = $guildPage["g_url"];
                                if(!empty($url)){
                                    echo '<a href="' . $guildPage["g_url"] . '" target="_top"><button class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect">Home Page</button></a>';
                                }
                            }
                            if (isset($guildPage["g_recruitment_url"])) {
                                $url = $guildPage["g_url"];
                                if(!empty($url)){
                                    echo '<a href="' . $guildPage["g_recruitment_url"] . '" target="_top"><button class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect">Recruitment Page</button></a>';
                                }
                            }
                            ?>
                    </div>
                    <div class="guild_page_description"><?php 
                            $htmlBBCode = $bbcode->parse(BBCodeUtils::convertSimilarTags($guildPage["g_description"]));
                            //Delay youtube video iframe loading due to it delaying the page layout js process
                            /*$ytUrls = array();
                            $end = 0;
                            $i = 0;
                            while($end !== false){
                                $offset = strpos($htmlBBCode, 'src="//www.youtube.com/embed/', $end);
                                if($offset){
                                    $end = strpos($htmlBBCode, '"', $offset + 10) + 1;
                                    $ytUrl = substr($htmlBBCode, $offset, $end - $offset);
                                    $htmlBBCode = str_replace($ytUrl, "class='yt_vid'", $htmlBBCode);
                                    $i++;
                                } else {
                                    break;
                                }
                            }
                            print_r($ytUrls);*/
                            $htmlBBCode = str_replace('<iframe width="560" height="315"', '<div class="yt_vid" width="560" height="315"', $htmlBBCode);
                            $htmlBBCode = str_replace('frameborder="0" allowfullscreen></iframe>', 'frameborder="0" allowfullscreen></div>', $htmlBBCode);
                            $htmlBBCode .= "<script>$(function() {setTimeout(function(){replaceTagName('.yt_vid', 'iframe');}, 500)});</script>";
                            
                            echo $htmlBBCode;
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="vertical-mdl-tabs-panels">
            <div class="mdl-tabs__panel" id="tab2">
                <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp table-td-ta-left compact-mdl-table" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="mdl-data-table__cell--non-numeric">Member</th>
                            <th class="mdl-data-table__cell--non-numeric">Rank</th>
                            <th class="mdl-data-table__cell--non-numeric">World</th>
                            <th class="mdl-data-table__cell--non-numeric">Member Since</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach(GW2DataPersistence::getGuildMembers($guildPage["g_uuid"], SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)) AS $membershipData){
                            echo '<tr>
                                    <td>'.$membershipData["a_username"].'</td>
                                    <td>'.$membershipData["g_rank"].'</td>
                                    <td>'.($membershipData["a_world"] > 0 ? GW2DataFieldConverter::getWorldNameById($membershipData["a_world"]) : "").'</td>
                                    <td>'.$membershipData["g_member_since"].'</td>
                                </tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="vertical-mdl-tabs-panels">
            <div class="mdl-tabs__panel" id="tab3">
                WIP
            </div>
        </div>
        <?php if($canAdminGuild){ 
            echo '<div class="vertical-mdl-tabs-panels">
                <div class="mdl-tabs__panel" id="tab4">
                    <p>Anyone with guild rights to edit roles, can use this page</p>
                    <form action="#" method="POST" class="clearfix">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label" style="width: 100%">
                            <input class="mdl-textfield__input" type="text" name="g_url" id="home-url" value="'.$guildPage["g_url"].'">
                            <label class="mdl-textfield__label" for="home-url">Home page URL</label>
                        </div>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label" style="width: 100%">
                            <input class="mdl-textfield__input" type="text" name="g_recruitment_url" id="recruitment-url" value="'.$guildPage["g_recruitment_url"].'">
                            <label class="mdl-textfield__label" for="recruitment-url">Recruitment URL</label>
                        </div>
                        <h5 style="margin-top: 0">Page Content</h5>
                        <textarea  name="g_description" id="page-content" class="bbcode-editor">'.$guildPage["g_description"].'</textarea>
                        <br />
                        <button name="form" value="update-data" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect">Save</button>
                    </form>';
            
            echo '  <br />
                    <form action="#" method="POST" class="clearfix">
                        <h5 style="margin-top: 0">Forum Sub Board</h5>
                        <p>If your guild does not have a website of your own, you can setup a sub board on this forum. However be aware that you do not have the same level of control, as you would with your own site</p>';
            
            $hasForumBoard = true;
            if($hasForumBoard){
                echo '<button disabled name="create-forum-board" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect">Create Forum Board</button>';
            } else {
                echo 'test';
            }
            
            
            echo '  </form>     
                    <br />
                    <h5 style="margin-top: 0">Guild Management Options</h5>
                    <form action="#" method="POST" class="clearfix">
                        <button disabled name="toggle-hide-show" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect" style="background-color: #c1aa07;">Hide</button>
                        <button name="refresh-data" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect">Refresh Guild Data from API</button>
                        <button name="delete" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect" style="background-color: #c10707; float: right">Delete</button>´
                    </form>
                </div>
            </div>';
        } ?>
    </div>
</div>

