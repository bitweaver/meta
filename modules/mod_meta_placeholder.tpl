{* $Header$ *}
{strip}
{if $gBitSystem->isPackageActive( 'meta' ) && count( $metaTables ) > 0}
	{bitmodule title="$moduleTitle" name="meta_placeholder"}
		{foreach from=$metaTables key=title item=groups}
			<table class="table metatable data">
			{foreach from=$groups key=name item=pairs}
				{if !($name eq '' )}
					<tr><td colspan="2">{$name}</td></tr>
				{/if}
				{foreach from=$pairs item=pair}
					<tr>
						<th><strong>{if $gContent->hasUserPermission('p_browse_meta')}<a href="{$smarty.const.META_PKG_URL}?metatt[{$pair.name|escape}]=*any*">{/if}{$pair.name}{if $gContent->hasUserPermission('p_browse_meta')}</a>{/if}</strong></th>
						<td>{if $gContent->hasUserPermission('p_browse_meta')}<a href="{$smarty.const.META_PKG_URL}?metatt[{$pair.name|escape}]={$pair.value|escape}">{/if}{$pair.value|escape}{if $gContent->hasUserPermission('p_browse_meta')}</a>{/if}</td>
					</tr>
				{/foreach}
			{/foreach}
			</table>
		{foreachelse}
			{tr}No information to display.{/tr}
		{/foreach}
	{/bitmodule}
{/if}
{/strip}
