<div class="header">
	<h1>{tr}Meta Search{/tr}</h1>
</div>
<div class="content">
{form ipackage="meta" ifile="index.php"}
	{jstabs tab="`$tab`"}
		{jstab title="Search"}
			{foreach from=$attributes key=attribute_id item=data}
			<div class="row">
				{formlabel label="`$data.name`" for="`$attribute_id`"}
				{forminput}
					<select name="metatt[{$attribute_id}]">
						<option value="any">{tr}Any value{/tr}</option>
						<option value="none">{tr}No value{/tr}</option>
						{foreach from=$data.values key=key item=value}
						<option value="{$value.id}">{$value.value}</option>
						{/foreach}
					</select>
				{/forminput}
			</div>
			{foreachelse}
			<p>
			{tr}No attributes defined.{/tr}
			</p>
			{/foreach}
			<div class="submit">
				<input type="submit" name="search" value="{tr}Search{/tr}"/>
			</div>
		{/jstab}
		{if $gBitSystem->isPackageActive( 'pigeonholes' )}
			{include file=bitpackage:pigeonholes/pigeonholes_input_inc.tpl}
		{/if}
		{jstab title="Results"}
			<table class="clear data">
				<tr>
					<th>{tr}Title{/tr}</th>
					<th>{tr}Last Modified{/tr}</th>
					<th>{tr}Author{/tr}</th>
				</tr>
				{foreach from=$searchData key=k item=res}
				<tr>
					<td><a href="{$smarty.const.BIT_ROOT_URL}?content_id={$res.id}">{$res.title}</a></td>
					<td>{$res.last_modified|bit_long_date}</td>
					<td>{$res.real_name}</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="3">{tr}No results found.{/tr}</td>
				</tr>
				{/foreach}
			</table>
		{/jstab}
	{/jstabs}
{/form}
</div>
