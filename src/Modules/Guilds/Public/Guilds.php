<?php

use Golonka\BBCode\BBCodeParser;
use GW2Integration\Modules\Guilds\GuildsPagesController;
use GW2Integration\Modules\Guilds\Persistence\GuildPagesPersistence;
use GW2Integration\Utils\BBCodeUtils;

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

require_once __DIR__ . "/../../../Source.php";
$bbcode = new BBCodeParser;

$guildName = filter_input(INPUT_GET, "name");
$isHelpPage = isset($_GET["help"]);


if (isset($guildName)) {
    $guildPage = GuildPagesPersistence::getGuildPageByName($guildName);
}

if (!isset($guildPage)) {
    $guildId = filter_input(INPUT_GET, "id");
    if (isset($guildId)) {
        $guildPage = GuildPagesPersistence::getGuildPage($guildId);
    }
}
?>

<!-- Material Icons -->
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<!-- MDL CSS -->
<link rel="stylesheet" href="https://code.getmdl.io/1.2.0/material.indigo-blue.min.css" />
<!-- MDL JS -->
<script src="https://code.getmdl.io/1.2.0/material.min.js"></script>

<!-- Custom CSS -->
<link rel="stylesheet" href="../../../Public/css/style.css">
<link rel="stylesheet" href="css/style.css">
<!-- Custom JS -->
<script src="../../../Public/js/jquery-2.2.3.min.js"></script>
<script src="../../../Public/js/common.js"></script>

<meta name="viewport" content="initial-scale=1, maximum-scale=1">

<div id="gw2i-container" <?php if(isset($_GET["dark-theme"])){echo 'class="dark-theme"';} ?>>
    <div id="gw2i-guild-pages">
        <?php
        if (isset($guildPage)) {
            include __DIR__ . "/Guild.php";
        } else if($isHelpPage) {
            include __DIR__ . "/Help.php";
        } else {
            $parentFrameURL = filter_input(INPUT_GET, "top-frame-url");
            $requestURL = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
            if (isset($parentFrameURL)) {
                $helpPage = $parentFrameURL . (parse_url($parentFrameURL, PHP_URL_QUERY) ? '&' : '?') . 'help';
            } else {
                $helpPage = $requestURL . (parse_url($requestURL, PHP_URL_QUERY) ? '&' : '?') . 'help';
            }
            echo '<div class="guild_page_help"><a href="'.$helpPage.'" target="_top" class="mdl-button mdl-js-button mdl-js-ripple-effect">Get your guild on here</a></div>';
            for ($category = 0; $category < 4; $category++) {
                echo '<h4 class="guild_cat_title">' . GuildsPagesController::getGuildPageCategoryFromId($category) . '</h4>';
                foreach (GuildsPagesController::getGuildPagesByCategory()[$category] AS $guildPage) {

                    if (isset($parentFrameURL)) {
                        $guildPageURL = $parentFrameURL . (parse_url($parentFrameURL, PHP_URL_QUERY) ? '&' : '?') . 'name=' . $guildPage["g_name"];
                        echo '<a class="mdl-card mdl-shadow--2dp guild_card" title="[' . $guildPage["g_tag"] . '] ' . $guildPage["g_name"] . '" href="' . $guildPageURL . '" target="_top">';
                    } else {
                        $guildPageURL = $requestURL . (parse_url($requestURL, PHP_URL_QUERY) ? '&' : '?') . 'name=' . $guildPage["g_name"];
                        echo '<a class="mdl-card mdl-shadow--2dp guild_card" title="[' . $guildPage["g_tag"] . '] ' . $guildPage["g_name"] . '" href="' . $guildPageURL . '">';
                    }

                    echo '  <div class="mdl-card__title mdl-card--expand guild_emblem_card" style="background-image: url(' . GuildsPagesController::getGuildEmblemURL($guildPage["g_uuid"]) . ')">
                                <h2 class="mdl-card__title-text guild_name_card">[' . $guildPage["g_tag"] . '] ' . $guildPage["g_name"] . '</h2>
                            </div>
                        </a>';
                }
            }
        }
        ?>
    </div>
</div>