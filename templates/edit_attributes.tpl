<div class="header">
	<h1>{tr}Manage Attributes{/tr}</h1>
</div>
<div class="content">
{if $metaAction eq 'edit'}
{form ipackage="meta" ifile="edit_attributes.php?action=edit&amp;id=`$metaId`"}
	<h1>{tr}Rename Attribute{/tr}</h1>
	<div class="control-group">
		{formlabel label="Name" for="name"}
		{forminput}
			<input type="text" name="name" id="name" value="{$metaName}"/>
		{/forminput}
	</div>
	<div class="control-group submit">
		<input type="submit" class="btn" name="edit_attribute" value="{tr}rename{/tr}" />
	</div>
{/form}
{elseif $metaAction eq 'delete'}
{form ipackage="meta" ifile="edit_attributes.php?id=`$metaId`"}
	<h1>{tr}Delete Attribute{/tr}</h1>
	<p>
	{tr}Do you really want to delete this attribute?{/tr} {$metaName}
	</p>
	<div class="control-group submit">
		<input type="submit" class="btn" name="delete_attribute" value="{tr}Confirm{/tr}" />
		<input type="submit" class="btn" name="action" value="{tr}Cancel{/tr}" />
	</div>
{/form}
{else}
{form ipackage="meta" ifile="edit_attributes.php"}
	<h1>{tr}New Attribute{/tr}</h1>
	<div class="control-group">
		{formlabel label="Name" for="name"}
		{forminput}
			<input type="text" name="name" id="name"/>
		{/forminput}
	</div>
	<div class="control-group submit">
		<input type="submit" class="btn" name="add_attribute" value="{tr}add{/tr}" />
	</div>
{/form}
{/if}
<table class="clear data">
	<tr>
		<th>{tr}Attribute Name{/tr}</th>
		<th>{tr}Number of Associations{/tr}</th>
		<th>{tr}Number of distinct values{/tr}</th>
		<th>{tr}Actions{/tr}</th>
	</tr>
	{foreach from=$attributes key=k item=data}
	<tr>
		<td>{$data.name}</td>
		<td>{$data.asso}</td>
		<td>{$data.val}</td>
		<td>
			<a href="edit_attributes.php?action=edit&amp;id={$data.id}">
				{biticon ipackage=liberty iname=edit iexplain="rename"}
			</a>
			<a href="edit_attributes.php?action=delete&amp;id={$data.id}">
				{biticon ipackage=liberty iname=delete iexplain="delete"}
			</a>
		</td>
	</tr>
	{foreachelse}
	<tr>
		<td rowspan="4">{tr}No attributes found.{/tr}</td>
	</tr>
	{/foreach}
</table>
</div>
