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
			<th>{$meta.name}</th>
			<td>{$meta.value}</td>
		</tr>
		{/foreach}
	{/foreach}
</table>
{/if}
