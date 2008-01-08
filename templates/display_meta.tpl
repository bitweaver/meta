{if count($metaInfo) > 0}
<table class="data meta">
	{foreach from=$metaInfo key=group item=elements}
		{if !($group eq '')}
		<tr>
			<th colspan="2">{$group}</th>
		</tr>
		{/if}
		{foreach from=$elements key=key item=meta}
		<tr>
			<th>{if $gContent->hasUserPermission('p_browse_meta')}<a href="{$smarty.const.META_PKG_URL}?metatt[{$meta.name}]">{/if}{$meta.name}{if $gContent->hasUserPermission('p_browse_meta')}</a>{/if}</th>
			<td>{$meta.value}</td>
		</tr>
		{/foreach}
	{/foreach}
</table>
{/if}
