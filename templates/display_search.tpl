<div class="header">
	<h1>{tr}Meta Search{/tr}</h1>
</div>
<div class="content">
{form ipackage="meta" ifile="index.php"}
	{jstabs tab="`$tab`"}
		{jstab title="Search"}
			{foreach from=$metaAttributes key=group item=attributes}
				{if !($group eq '')}
					<h2>{$group}</h2>
				{/if}
				{foreach from=$attributes key=attribute_id item=data}
				{assign var="metaName" value=$data.name}
				<div class="row">
					{formlabel label="`$data.name`" for="`$attribute_id`"}
					{forminput}
						<select name="metatt[{$metaName|escape}]">
							<option value=""></option>
							<option value="*any*" {if $smarty.request.metatt.$metaName=='*any*'}selected="selected"{/if}>{tr}Any Assigned Value{/tr}</option>
							{foreach from=$data.values key=key item=value}
							<option value="{$value.value|escape}" {if $smarty.request.metatt.$metaName==$value.value}selected="selected"{/if}>{$value.value}</option>
							{/foreach}
						</select>
					{/forminput}
				</div>
				{/foreach}
			{foreachelse}
			<p>
			{tr}No attributes defined.{/tr}
			</p>
			{/foreach}
			<div class="submit">
				<input type="submit" name="search" value="{tr}Search{/tr}"/>
			</div>
		{/jstab}
	{/jstabs}
		{if $searchData}
			<h1>{tr}Search Results{/tr}</h1>
			<table class="clear data">
				<tr>
					<th>{tr}Title{/tr}</th>
					<th>{tr}Last Modified{/tr}</th>
					<th>{tr}Author{/tr}</th>
					<th>{tr}Value{/tr}</th>
				</tr>
				{foreach from=$searchData key=k item=res}
				<tr>
					<td><a href="{$smarty.const.BIT_ROOT_URL}?content_id={$res.content_id}">{$res.title|escape}</a></td>
					<td>{$res.last_modified|bit_long_date}</td>
					<td>{$res.real_name}</td>
					<td>{$res.meta|@implode:' => '}</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="3">{tr}No results found.{/tr}</td>
				</tr>
				{/foreach}
			</table>
		{/if}
{/form}
</div>
