{**
 * plugins/importexport/native/templates/results.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}

{if $validationErrors}
	<h2>{translate key="plugins.importexport.common.validationErrors"}</h2>
	<ul>
		{foreach from=$validationErrors item=validationError}
			<li>{$validationError->message|escape}</li>
		{/foreach}
	</ul>
{else}
	{translate key="plugins.importexport.native.importComplete"}
	<ul>
		{foreach from=$content item=contentItem}
			<li>
				{if is_a($contentItem, 'Submission')}
					{$contentItem->getLocalizedTitle()|strip_unsafe_html}</li>
				{else}
					{$contentItem->getIssueIdentification()|escape}
				{/if}
			</li>
		{/foreach}
	</ul>
{/if}