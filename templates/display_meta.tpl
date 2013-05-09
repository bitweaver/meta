{if count($metaInfo) > 0}
<div class="meta well pull-right nopadding">
<table class="table data meta">
	{foreach from=$metaInfo key=group item=elements}
		{if !($group eq '')}
		<tr>
			<th colspan="2">{$group}</th>
		</tr>
		{/if}
		{foreach from=$elements key=key item=meta}
		<tr>
			<td><strong>{if $gContent->hasUserPermission('p_browse_meta')}<a href="{$smarty.const.META_PKG_URL}?metatt[{$meta.name|escape}]=*any*">{/if}{$meta.name}{if $gContent->hasUserPermission('p_browse_meta')}</a>{/if}</strong></td>
			<td>{if $gContent->hasUserPermission('p_browse_meta')}<a href="{$smarty.const.META_PKG_URL}?metatt[{$meta.name|escape}]={$meta.value|escape}">{/if}{$meta.value|escape}{if $gContent->hasUserPermission('p_browse_meta')}</a>{/if}</td>
		</tr>
		{/foreach}
	{/foreach}
</table>
</div>
{/if}
