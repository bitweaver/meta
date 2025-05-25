<div class="header">
	<h1>{$smarty.const.META_PKG_DIR|ucwords} {tr}Search{/tr}</h1>
</div>
<div class="content">
{form ipackage="meta" method="get"}
	<div class="row">
		<div class="col-xs-12 col-sm-4">
			{jstabs}
				{jstab title="Search Fields"}
				{foreach from=$metaAttributes key=group item=attributes}
					{if !($group eq '')}
						<h2>{$group}</h2>
					{/if}
					{foreach from=$attributes key=attribute_id item=data}
					{assign var="metaName" value=$data.name}
					<div class="form-group {if !empty($smarty.request.metatt.$metaName)}has-warning{/if}">
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
					<input type="submit" class="btn btn-primary" name="search" value="{tr}Search{/tr}"/>
				</div>
				{/jstab}
			{/jstabs}
		</div>
		<div class="col-xs-12 col-sm-8">
			{jstabs}
				{jstab title="Results"}
				{foreach from=$searchData key=groupKey item=groupResults}
				<h3>{$groupKey|escape}</h3>
				<table class="table data">
					<tr>
						<th>{tr}Title{/tr}</th>
						<th>{tr}Last Modified{/tr}</th>
						<th>{tr}Author{/tr}</th>
						<th>{tr}Value{/tr}</th>
					</tr>
					{foreach from=$groupResults key=k item=res}
					<tr>
						<td><a href="{$smarty.const.BIT_ROOT_URL}?content_id={$res.content_id}">{$res.title|escape}</a></td>
						<td>{$res.last_modified|bit_short_date}</td>
						<td>{$res.real_name}</td>
						<td>{foreach from=$res.meta item=value key=name}{$name} &rarr; {$value}<br/>{/foreach}</td>
					</tr>
					{foreachelse}
					<tr>
						<td colspan="3">{tr}No results found. Choose different criteria.{/tr}</td>
					</tr>
					{/foreach}
				</table>
				{/foreach}
				{/jstab}
			{/jstabs}
		</div>
	</div>
{/form}
</div>
