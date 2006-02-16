{if $gBitUser->hasPermission( 'bit_p_assign_meta' )}
{jstab title="Meta Attributes"}
	<h1>{tr}Meta Attributes{/tr}</h1>

	{foreach from=$metaAttributes key=attribute_id item=data}
	<div class="row">
		{formlabel label="`$data.name`" for="`$attribute_id`"}
		{forminput}
			<select name="metatt[{$attribute_id}]">
				{foreach from=$data.values key=key item=value}
				<option value="{$value.id}"{if $value.selected > 0} selected="selected"{/if}>{$value.value}</option>
				{/foreach}
			</select>
			
			{if $gBitUser->hasPermission( 'bit_p_edit_value_meta' )}
				<input type="text" name="metatt_other[{$attribute_id}]" value="{$metaAttributesOther[$attribute_id]}"/>
			{/if}
		{/forminput}
	</div>
	{foreachelse}
	<p>
	{tr}No attributes defined.{/tr}
	</p>
	{/foreach}
	{if $gBitUser->hasPermission( 'bit_p_edit_attribute_meta' )}
	<p>
	<a href="{$smarty.const.META_PKG_URL}edit_attributes.php">{tr}Manage Attributes{/tr}</a>
	</p>
	{/if}
{/jstab}
{/if}
