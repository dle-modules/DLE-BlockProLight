<div id="{$block_id}">
	{$pages}
	{if $list|length > 0}
		<h3 style="text-align: center">Новости выведены с помощью модуля DLE-BlockProLight</h3>
			{* Пробегаем по массиву с новостями *}
			{foreach $list as $key => $el}
				<div class="box story shortstory">
					<div class="box_in">
						<h2 class="title">
							<a href="{$el.url}">{$el.title}</a>
						</h2>
						<div class="text">
								{$el.short_story}
						</div>

					</div>
					<div class="meta">
						<ul class="right">
							<li>
									{set $symbols=$el.short_story|length}
								В новости: <b>{$symbols}</b> {$symbols|declination:'символ||а|ов'}
							</li>
						</ul>
						<ul class="left">
							<li class="story_date">
								Опубликовано: {$el.date|timeago:1}
							</li>
						</ul>
					</div>
				</div>
			{/foreach}
	{/if}
</div> <!-- /#{$block_id} -->



