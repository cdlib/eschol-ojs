{**
 * setupHeader.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for journal setup pages.
 *
 * $Id$
 *}
{strip}
{assign var="pageCrumbTitle" value="manager.setup.journalSetup"}
{url|assign:"currentUrl" op="setup"}
{include file="common/header.tpl"}
{/strip}

<ul class="steplist">
	<li id="step1" {if $setupStep == 1} class="current"{/if}><a href="{url op="setup" path="1"}">1. {translate key="manager.setup.details"}</a></li>
	<li id="step2" {if $setupStep == 2} class="current"{/if}><a href="{url op="setup" path="2"}">2. {translate key="manager.setup.policies"}</a></li>
	<li id="step3" {if $setupStep == 3} class="current"{/if}><a href="{url op="setup" path="3"}">3. {translate key="manager.setup.submissions"}</a></li>
	<li id="step4" {if $setupStep == 4} class="current"{/if}><a href="{url op="setup" path="4"}">4. {translate key="manager.setup.management"}</a></li>
	<li id="step5" {if $setupStep == 5} class="current"{/if}><a href="{url op="setup" path="5"}">5. {translate key="manager.setup.look"}</a></li>
        <!-- MH-6-STEP: Add this back in when ready to reveal Step 6
        <li id="step6" {if $setupStep == 6} class="current"{/if}><a href="{url op="setup" path="6"}">6. Public Site</a></li>
        -->
        <!-- MH-7-STEP: Add this back in when ready to reveal Step 7
        <li id="step7" {if $setupStep == 7} class="current"{/if}><a href="{url op="setup" path="6"}">7. Batch import/export</a></li>
        -->
</ul>

