<p>@{$user->username}:</p>
{if $post|isset}
<p><br></p>
<p>
Your
<a href="{$post->getLink()}">Request</a>
was
<strong>
<span style="font-family:Arial, Helvetica, sans-serif; color:{if $approved}#006400{else}#FF0000{/if}">
	{if $approved}APPROVED{else}DENIED{/if}
</span>
</strong>
by the USRO.
</p>
{/if}
{if $message|isset}
<p><br></p>
<p>{$message}</p>
{/if}
{if $children|isset && $children|count > 0}
<p><br></p>
<p>This also affected the following State-IDs:</p>
<ul>
	{foreach from=$children item=$child}
	<li>
		[b]{$child->username}[/b],
		[i]{$child->locationName}[/i]
	</li>
	{/foreach}
</ul>
{/if}
