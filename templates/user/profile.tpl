{**
 * templates/user/profile.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile tabset.
 *}
{capture assign="additionalProfileTabs"}
	<li><a name="notifications" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.user.OJSProfileTabHandler" op="notifications"}">{translate key="notification.notifications"}</a></li>
{/capture}
{include file="core:user/profile.tpl" additionalProfileTabs=$additionalProfileTabs}
