{* $Header: /cvsroot/bitweaver/_bit_meta/modules/mod_meta_placeholder.tpl,v 1.1 2006/02/24 00:51:58 lphuberdeau Exp $ *}
{strip}
{if $gBitSystem->isPackageActive( 'meta' ) and count( $metaTables ) > 0}
	{bitmodule title="$moduleTitle" name="meta_placeholder"}
		{foreach from=$metaTables key=title item=groups}
			<table class="metatable data">
				<caption>{$title}</caption>
				{foreach from=$groups key=name item=pairs}
					{if !($name eq '' )}
						<tr><td colspan="2">{$name}</td></tr>
					{/if}
					{foreach from=$pairs item=pair}
						<tr>
							<th>{$pair.name}</th>
							<td>{$pair.value}</td>
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