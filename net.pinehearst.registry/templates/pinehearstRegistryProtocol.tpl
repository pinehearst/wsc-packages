<p class="text-center">
	<h1>United States Electronic Registration System</h1>
	<h3>Last Update: {$now}</h3>
</p>

[tabmenu]
[tab='By Location']
{foreach from=$locations key=locationName item=entries}
[subtab='{$locationName}']
{if $entries|empty}
	<h2>Currently nobody is registered in <strong>{$locationName}</strong>!</h2>
{else}
<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Registered</th>
			<th>Location</th>
			<th>Sponsor</th>
			<th>Last Activity</th>
		</tr>
	</thead>
	<tbody>
		{foreach from=$entries item=$entry}
		<tr>
			<td>
				<a href="{$entry->user->getLink()}">
				{if $entry->parentID}
				{$entry->user->username}
				{else}
				<strong>{$entry->user->username}</strong>
				{/if}
				</a>
			</td>
			<td title="{$entry->registeredOnAbsolute}">{$entry->registeredOnRelative}</td>
			<td>
				<a href="{$entry->location->board->getLink()}">
				{$entry->location->locationName}
				</a>
			</td>
			<td>
				{if $entry->parentID > 0}
					<a href="{$entry->parent->user->getLink()}">
					{$entry->parent->user->username}
					</a>
				{else}
					-/-
				{/if}
			</td>
			<td title="{$entry->lastActivityAbsolute}">
				{if $entry->postID}
					{if $entry->showDetails}
						{$entry->lastActivityRelative} in <a href="{$entry->post->getLink()}">{$entry->thread->topic}</a>
					{else}
						{$entry->lastActivityRelative} in a Private Board
					{/if}
				{else}
					Registration
				{/if}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>
{/if}
[/subtab]
{/foreach}
[/tab]
[tab='By Type']
[subtab='Federal-IDs']
{if $parents|empty}
	<h2>Currently nobody is registered as a <strong>Federal-ID</strong>.</h2>
{else}
<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Registered</th>
			<th>Location</th>
			<th>Last Activity</th>
		</tr>
	</thead>
	<tbody>
		{foreach from=$parents item=$entry}
		<tr>
			<td>
				<a href="{$entry->user->getLink()}">
				{$entry->user->username}
				</a>
			</td>
			<td title="{$entry->registeredOnAbsolute}">{$entry->registeredOnRelative}</td>
			<td>
				<a href="{$entry->location->board->getLink()}">
				{$entry->location->locationName}
				</a>
			</td>
			<td title="{$entry->lastActivityAbsolute}">
				{if $entry->postID}
					{if $entry->showDetails}
						{$entry->lastActivityRelative} in <a href="{$entry->post->getLink()}">{$entry->thread->topic}</a>
					{else}
						{$entry->lastActivityRelative} in a Private Board
					{/if}
				{else}
					Registration
				{/if}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>
{/if}
[/subtab]
[subtab='State-IDs']
{if $children|empty}
	<h2>Currently nobody is registered as a <strong>State-ID</strong>!</h2>
{else}
<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Registered</th>
			<th>Location</th>
			<th>Sponsor</th>
			<th>Last Activity</th>
		</tr>
	</thead>
	<tbody>
		{foreach from=$children item=$entry}
		<tr>
			<td>
				<a href="{$entry->user->getLink()}">
				{$entry->user->username}
				</a>
			</td>
			<td title="{$entry->registeredOnAbsolute}">{$entry->registeredOnRelative}</td>
			<td>
				<a href="{$entry->location->board->getLink()}">
				{$entry->location->locationName}
				</a>
			</td>
			<td>
				<a href="{$entry->parent->user->getLink()}">
				{$entry->parent->user->username}
				</a>
			</td>
			<td title="{$entry->lastActivityAbsolute}">
				{if $entry->postID}
					{if $entry->showDetails}
						{$entry->lastActivityRelative} in <a href="{$entry->post->getLink()}">{$entry->thread->topic}</a>
					{else}
						{$entry->lastActivityRelative} in a Private Board
					{/if}
				{else}
					Registration
				{/if}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>
{/if}
[/subtab]
[/tab]
[/tabmenu]
