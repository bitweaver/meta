{if count($metaInfo) > 0}
<table class="data meta">
	{foreach from=$metaInfo key=key item=meta}
	<tr>
		<th>{$meta.name}</th>
		<td>{$meta.value}</td>
	</tr>
	{/foreach}
</table>
{/if}
