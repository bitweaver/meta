{if $gBitUser->hasPermission( 'p_assign_meta' )}
{jstab title=$smarty.const.META_PKG_TITLE}
	{legend legend="`$smarty.const.META_PKG_TITLE` Assignments"}

	{foreach from=$metaAttributes key=group item=attributes}
		{if !($group eq '')}
			<h2>{$group}</h2>
		{/if}
		{foreach from=$attributes key=attribute_id item=data}
		<div class="form-group">
			{formlabel label="`$data.name`" for="`$attribute_id`"}
			{forminput}
				<select name="metatt[{$attribute_id}]" id="metattmenu">
					{foreach from=$data.values key=key item=value}
					<option value="{$value.id}"{if $value.selected > 0} selected="selected"{/if}>{$value.value}</option>
					{/foreach}
				</select>
				
				{if $gBitUser->hasPermission( 'p_edit_value_meta' )}
					<input type="text" name="metatt_other[{$attribute_id}]" value="{$metaAttributesOther[$attribute_id]}" onkeyup=""/>
				{/if}
			{/forminput}
		</div>
		{/foreach}
	{foreachelse}
	<p>
	{tr}No attributes defined.{/tr}
	</p>
	{/foreach}
	{if $gBitUser->hasPermission( 'p_edit_attribute_meta' )}
		<p> <a href="{$smarty.const.META_PKG_URL}edit_attributes.php">{tr}Manage {$smarty.const.META_PKG_TITLE} Options{/tr}</a> </p>
	{/if}
	
	{/legend}
{/jstab}
{/if}
