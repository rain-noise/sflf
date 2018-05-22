{*
=====================================================================
■パラメータ
 - pi     : PageInfo object
 - size   : Pager length size
 - type   : URL or FORM
 - url    : [URL]  URL like /user/search?page= 
 - submit : [FORM] submit button html tag selecter
 - hidden : [FORM] hidden html tag selecter of page
 
■呼び出し例
　○ URL形式
　{include file='common/paginate.tpl' pi=$pi size=5 type='URL' url='/user/search?page='}

　○ FORM形式
　{include file='common/paginate.tpl' pi=$pi size=5 type='FORM' submit='#serch-button' hidden='[name=page]'}

■スタイル定義例
.paginate { margin-bottom: 50px; }
.paginate .info { float: left; }
.paginate .info .hitCount { font-size: 24px; color: #f33; margin-left: 10px; }
.paginate .info .hitCount .unit { font-size: 16px; color: #333; }
.paginate .info .offset { color: #00f; margin-left: 10px; }
.paginate .info .offset .unit { color: #333; }
.paginate .info .limit { color: #00f; }
.paginate .info .limit .unit { color: #333; }
.paginate .pager { float: right; font-size: 14px; margin-top: 5px; }
.paginate .pager li { display: inline-block; }
.paginate .pager li a { border: 1px solid #eee; background-color: #eee; padding: 5px 10px 5px; border-radius: 4px; -webkit-border-radius: 4px; -moz-border-radius: 4px; color: #333; }
.paginate .pager li a:HOVER { background-color: #666; color: #fff; border-color: #666; }
.paginate .pager li span { border: 1px solid #eee; padding: 5px 10px 5px; border-radius: 4px; -webkit-border-radius: 4px; -moz-border-radius: 4px; color: #333; }

@package   SFLF
@version   v1.0.0
@author    github.com/rain-noise
@copyright Copyright (c) 2017 github.com/rain-noise
@license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
=======================================================================
*}
{if $type == 'FORM'}
<script type="text/javascript">
<!--
	var pageJump = function(page) {
		jQuery('{$hidden}').val(page);
		jQuery('{$submit}').click();
	};
//-->
</script>
{else}
<script type="text/javascript">
<!--
	var pageJump = function(page) {
		location.href = '{$url}' + page;
	};
//-->
</script>
{/if}
<div class="paginate">
	<div class="info">
		該当件数：<span class="hitCount">{$pi->hitCount}<span class="unit"> 件</span></span>
		<span class="offset">{$pi->offset}<span class="unit"> 件</span></span> ～ <span class="limit">{$pi->limit}<span class="unit"> 件</span></span> を表示
	</div>
	{if !$pi->isEmpty()}
	<ul class="pager">
		<li class="first-page">{if !$pi->isFirstPage()}<a href="javascript:void(0);" onclick="pageJump(1)">先頭へ</a>{else}<span>先頭へ</span>{/if}</li>
		<li class="prev-page">{if  $pi->hasPrevPage()}<a href="javascript:void(0);" onclick="pageJump({$pi->page - 1})">前へ</a>{else}<span>前へ</span>{/if}</li>
		{foreach from=$pi->getNeighborPages($size) item='page'}
			{if $pi->page == $page}
				<li class="current-page"><span>{$page}</span></li>
			{else}
				<li class="page"><a href="javascript:void(0);" onclick="pageJump({$page});">{$page}</a></li>
			{/if}
		{/foreach}
		<li class="next-page">{if  $pi->hasNextPage()}<a href="javascript:void(0);" onclick="pageJump({$pi->page + 1});">次へ</a>{else}<span>次へ</span>{/if}</li>
		<li class="prev-page">{if !$pi->isLastPage()}<a href="javascript:void(0);" onclick="pageJump({$pi->maxPage});">最後へ</a>{else}<span>最後へ</span>{/if}</li>
	</ul>
	{/if}
</div>
