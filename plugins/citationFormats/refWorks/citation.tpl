{**
 * plugins/citationFormats/refWorks/citation.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation
 *
 *}
<form class="refworks_citation_form" action="http://www.refworks.com/express/expressimport.asp?vendor=Public%20Knowledge%20Project&filter=BibTeX&encoding=65001" method="post" target="RefWorksMain">
	{csrf}
	<textarea name="ImportData" rows=15 cols=70>{literal}@article{{{/literal}{$journal->getLocalizedAcronym()|escape}{literal}}{{/literal}{$articleId|escape}{literal}},
	author = {{/literal}{assign var=authors value=$article->getAuthors()}{foreach from=$authors item=author name=authors key=i}{$author->getLastName()|escape}, {assign var=firstName value=$author->getFirstName()}{assign var=authorCount value=$authors|@count}{$firstName|escape|truncate:1}.{if $i<$authorCount-1}, {/if}{/foreach}{literal}},
	title = {{/literal}{$article->getLocalizedTitle()|strip_unsafe_html}{literal}},
	journal = {{/literal}{$journal->getLocalizedName()|escape}{literal}},
{/literal}{if $issue}{literal}	volume = {{/literal}{$issue->getVolume()|escape}{literal}},
	number = {{/literal}{$issue->getNumber()|escape}{literal}},{/literal}{/if}{literal}
	year = {{/literal}{if $article->getDatePublished()}{$article->getDatePublished()|date_format:'%Y'}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|date_format:'%Y'}{else}{$issue->getYear()|escape}{/if}{literal}},
{/literal}{assign var=issn value=$journal->getSetting('issn')|escape}{if $issn}{literal}	issn = {{/literal}{$issn|escape}{literal}},{/literal}{/if}
{if $article->getStoredPubId('doi')}	doi = {ldelim}{$article->getStoredPubId('doi')|escape}{rdelim},
{/if}
{literal}	url = {{/literal}{$articleUrl}{literal}}
}{/literal}</textarea>
	<input type="submit" class="button defaultButton" name="Submit" value="{translate key="plugins.citationFormats.refWorks.export"}" />
</form>
