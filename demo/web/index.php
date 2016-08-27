<!DOCTYPE html>
<html>

<?php
/*
 * Redirect mobile visitors to PC/tablet,
 * because mathquill does not support mobile.
 */
require_once 'vendor/mobile-detect/Mobile_Detect.php';
$detect = new Mobile_Detect;
if ($detect->isMobile()) {
	header("Location: mobile.php");
	exit;
}
?>

<head>
<title>Approach0</title>
<meta charset="utf-8"/>
<meta name="description" content="Approach Zero: A math-aware search engine. Search Mathematics Stack Exchange.">
<link rel="shortcut icon" href="images/favicon.ico">
<link rel="stylesheet" href="vendor/mathquill/mathquill.css" type="text/css"/>
<link rel="stylesheet" href="vendor/katex/katex.min.css" type="text/css"/>
<link rel="stylesheet" href="search.css" type="text/css"/>
<link rel="stylesheet" href="qry-box.css" type="text/css"/>
<link rel="stylesheet" href="font.css" type="text/css"/>
<link rel="stylesheet" href="quiz.css" type="text/css"/>

<script type="text/javascript" src="vendor/jquery/jquery-1.12.4.min.js"></script>
<script type="text/javascript" src="vendor/vue/vue.min.js"></script>
<script type="text/javascript" src="vendor/mathquill/mathquill.min.js"></script>
<script type="text/javascript" src="vendor/katex/katex.min.js"></script>
<script type="text/javascript" src="vendor/typed/typed.js"></script>
<script type="text/javascript" src="tex-render.js"></script>
<script type="text/javascript" src="search.js"></script>
<script type="text/javascript" src="quiz-list.js"></script>
<script type="text/javascript" src="quiz.js"></script>
<script type="text/javascript" src="qry-box.js"></script>
<script>
$.fn.stickToBottom = function () {
	var ceil_ele = $('#quiz');
	var ceil_pos = ceil_ele.offset().top + ceil_ele.outerHeight();
	var wind_height = window.innerHeight;
	var this_margin_top = parseInt($(this).css('margin-top'), 10);
	var this_height = $(this).outerHeight() + this_margin_top;
	var space = wind_height - ceil_pos;

	if (space > this_height) {
		$(this).css({
			position: 'absolute',
			bottom: 0
		});
	} else {
		$(this).css({
			position: 'static'
		});
	}
};

$(document).ready(function() {
	$('#init-footer').stickToBottom();
});

$(window).resize(function() {
	$('#init-footer').stickToBottom();
});
</script>
<style>
img.social {
	height: 16px;
}
div.center-v-pad {
	height: 200px
}
div.center-horiz {
	margin: 0 auto;
	text-align: center;
}
div.stick-bottom {
	position: absolute;
	bottom: 0;
	width: 100%;
}
</style>
</head>

<body style="margin: 0; border-top: 2px solid #46ece5;">

<!-- Query Box App -->
<div id="qry-input-vue-app" style="padding: 8px 8px 10px 8px;
box-shadow: 0 0 4px rgba(0,0,0,0.25);">

<!-- Query input area -->
<div>
<div id="qry-input-area" style="width:100%;" v-on:click="area_on_click">
<ul class="qry-li-wrap"><template v-for="i in items">
		<li v-if="i.type == 'term'" class="qry-li">
			<div class="qry-div-fix">
				<span>{{{i.str}}}</span>
				<span title="delete" class="dele" v-bind:onclick="'dele_kw('+$index+')'">×</span>
			</div>
		</li>
		<li v-if="i.type == 'tex'" class="qry-li">
			<div class="qry-div-fix">
				<span>[imath]{{i.str}}[/imath]</span>
				<span title="delete" class="dele" v-bind:onclick="'dele_kw('+$index+')'">×</span>
				<span title="edit" class="edit" v-bind:onclick="'edit_kw('+$index+')'">✐</span>
			</div>
		</li>
		<li v-if="i.type == 'term-input'" class="qry-li">
			<input v-on:keyup="on_input" v-model="i.str" type="text" id="qry-input-box"
			class="pl_holder" placeholder="Enter keywords here, type $ for math keyword."/>
		</li>
		<li v-if="i.type == 'tex-input'" class="qry-li">
			<span id="math-input"></span>
			<span class="pl_holder">When you finish editing this math, press enter.</span>
		</li>
</template></ul>
</div>
</div>
<!-- Query input area END -->

<!-- Search button and options -->
<div style="margin-top: 8px;">
	<button style="float:right; margin-right: 5px;" type="button" id="search_button">Search</button>

	<span class="collapse" title="Lookup Math symbols">(+) math symbols</span>
	<div>
		<p> [ TODO: Some buttons here ] </p>
		<hr class="vsep"/>
	</div>

	<span class="collapse" title="Raw query and API">(+) raw query</span>
	<div>
		<p>If you know TeX, it is faster to edit the equivalent raw query
		(separate keywords by commas):</p>
		<input id="qry" style="width:100%;" type="text" v-model="raw_str" v-on:keyup="on_rawinput" placeholder="empty"/>
		<p>Its corresponding WEB API:</p>
		<p style="background-color: black; color: #bbb; padding: 3px 0 3px 6px; overflow-x: auto; white-space: nowrap;">
		curl -v {{url_root}}search-relay.php?q='{{enc_uri}}'
		<p>

	</div>

</div>
<!-- Search button and options END -->

</div>
<!-- Query Box App END -->

<!-- Quiz App -->
<div id="quiz-vue-app" v-show="!hide">
	<div id="quiz">
		<div class="center-v-pad"></div>
		<div class="center-horiz">
			<p id="quiz-question">
			<b>Question</b>: &nbsp; {{Q}}
			</p>
		</div>
		<div class="center-horiz" style="padding-top:20px;">
			<span id="quiz-hint" class="mainfont"></span>
		</div>
	</div>

	<!-- Initial Footer -->
	<div v-show="!hide" id="init-footer" class="center-horiz"
	style="font-size: small; margin-top: 40px; width: 100%;
	bottom: 0px; position: absolute; background: #fbfefe;
	padding-bottom: 15px; padding-top: 15px;
	box-shadow: 0 0 4px rgba(0,0,0,0.25);">
	<a target="_blank" href="https://twitter.com/approach0">
	<img style="vertical-align:middle"
	src="images/logo32.png"/></a>
	+
	<a target="_blank" href="http://math.stackexchange.com/">
	<img style="vertical-align:middle"
	src="images/math-stackexchange.png"/></a>
	+
	<span style="color: red;">♡ </span>
	=
	<p>A math-aware search engine for Mathematics Stack Exchange.</p>
	</div>
	<!-- Initial Footer END -->

</div>
<!-- Quiz App END -->

<!-- Search App -->
<div id="search-vue-app">

<!-- Error code -->
<div v-if="ret_code > 0">
<div class="center-v-pad"></div>
<div class="center-horiz">
	<p>Opps! {{ret_str}}. (return code #{{ret_code}})</p>
</div>
</div>
<!-- Error code END -->

<!-- Search Results -->
<div v-if="ret_code == 0">
	<ol>
	<li v-for="hit in hits">
		<a target="_blank" v-bind:href="hit.url">{{hit.url}}</a><br/>
		<p class="snippet">{{{ hit.snippet }}}</p>
	</li>
	</ol>
</div>
<!-- Search Results END -->

<!-- Footer -->
<div v-show="ret_code == 0"
style="padding-top: 20px; height: 30px; background: #fbfefe;
box-shadow: 0 0 4px rgba(0,0,0,0.25);">

<!-- Left Footer -->
	<div style="float: left; padding-left: 20px;">
		<span v-if="prev != ''">
			← <a class="page-navi" v-bind:onclick="prev" href="#">prev</a>
		</span>
		<span class="mainfont">[page {{cur_page}}/{{tot_pages}}]</span>
		<span v-if="next != ''">
			<a class="page-navi" v-bind:onclick="next" href="#">next</a> →
		</span>
	</div>

<!-- Right Footer -->
	<div style="float: right;">
		<a href="https://twitter.com/intent/tweet?text=Check%20this%20out%3A%20%40Approach0%2C%20A%20math-aware%20search%20engine%3A%20http%3A%2F%2Fwww.approach0.xyz"
		target="_blank" title="Tweet" class="twitter-share-button">
		<img class="social" src="images/social/Twitter.svg"></a>

		<a href="https://plus.google.com/share?url=https%3A%2F%2Fwww.approach0.xyz"
		target="_blank" title="Share on Google+">
		<img class="social" src="images/social/Google+.svg"></a>

		<a href="http://www.reddit.com/submit?url=https%3A%2F%2Fwww.approach0.xyz&title=Check%20out%20this%20math-aware%20search%20engine!"
		target="_blank" title="Submit to Reddit">
		<img class="social" src="images/social/Reddit.svg"></a>

		<script async defer src="https://buttons.github.io/buttons.js"></script>
		<a class="github-button" href="https://github.com/approach0/search-engine"
		data-count-href="/approach0/search-engine/stargazers" data-count-api="/repos/approach0/search-engine#stargazers_count"
		data-count-aria-label="# stargazers on GitHub" aria-label="Star approach0/search-engine on GitHub">Star</a>
	</div>

</div>
<!-- Footer END -->

</div>
<!-- Search App END -->

</body>
</html>
