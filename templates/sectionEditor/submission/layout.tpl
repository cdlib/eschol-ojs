{**
 * layout.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the layout editing table.
 *
 * $Id$
 *
 * CHANGELOG:
 *	20110729	BLH	Add "Generate PDF" button, which will generate a PDF version of the existing Layout Version and add it as the Galley Version.
 *	20110803 	BLH When user clicks "Generate PDF" button, make sure they can't click it a 2nd time.
 *}

{literal}
<script type="text/javascript">
	
	function hideGeneratePdfButton() {
		$("#generatePdf").hide();
		$("#generatePdf").hide();
		alert("{/literal}{translate key="submission.layout.generatePdfPleaseWait"}{literal}");
	}

	function checkForFileToUpload() {
        	var fieldValue = document.uploadFile.layoutFile.value;
        	if (!fieldValue) {
                	alert("Please select a file by clicking 'Browse...' first.");
                	return false;
       		}
        	return true;
	}

</script>
{/literal}

{assign var=layoutSignoff value=$submission->getSignoff('SIGNOFF_LAYOUT')}
{assign var=layoutFile value=$submission->getFileBySignoffType('SIGNOFF_LAYOUT')}
{assign var=layoutEditor value=$submission->getUserBySignoffType('SIGNOFF_LAYOUT')}

<div id="layout">
<h3>{translate key="submission.layout"} <a href="https://submit.escholarship.org/help/journals/editors_r.html" target="_blank"><img src="{$baseUrl}/eschol/images/help_A.png"></a></h3>

{if $useLayoutEditors}
<div id="layoutEditors">
<table class="data" width="100%">
	<tr>
		<td width="20%" class="label">{translate key="user.role.layoutEditor"}</td>
		{if $layoutSignoff->getUserId()}
                    {if $layoutEditor}
                        <td width="20%" class="value">{$layoutEditor->getFullName()|escape}</td>
                    {else}
                        <td style="padding: 5px; background: #F0B1A8;"><strong>Error</strong>: Layout Editor with User ID {$layoutSignoff->getUserId()} is missing from the database. Please contact {mailto address='help@escholarship.org'} to resolve this issue.</td>
                    {/if}
                {/if}
                <td class="value"><a href="{url op="assignLayoutEditor" path=$submission->getId()}" class="action">{translate key="submission.layout.assignLayoutEditor"}</a></td>
	</tr>
</table>
</div>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="28%" colspan="2">&nbsp;</td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="16%" class="heading">{translate key="submission.underway"}</td>
		<td width="16%" class="heading">{translate key="submission.complete"}</td>
		<td width="22%" colspan="2" class="heading">{translate key="submission.acknowledge"}</td>
	</tr>
	<tr>
		<td colspan="2">
			{translate key="submission.layout.layoutVersion"}
		</td>
		<td>
			{if $useLayoutEditors}
				{if $layoutSignoff->getUserId() && $layoutFile}
					{url|assign:"url" op="notifyLayoutEditor" articleId=$submission->getId()}
					{if $layoutSignoff->getDateUnderway()}
                                        	{translate|escape:"javascript"|assign:"confirmText" key="sectionEditor.layout.confirmRenotify"}
                                        	{icon name="mail" onclick="return confirm('$confirmText')" url=$url}
                                	{else}
                                        	{icon name="mail" url=$url}
                                	{/if}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$layoutSignoff->getDateNotified()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if $useLayoutEditors}
				{$layoutSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if $useLayoutEditors}
				{$layoutSignoff->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td colspan="2">
			{if $useLayoutEditors}
				{if $layoutSignoff->getUserId() &&  $layoutSignoff->getDateCompleted() && !$layoutSignoff->getDateAcknowledged()}
					{url|assign:"url" op="thankLayoutEditor" articleId=$submission->getId()}
					{icon name="mail" url=$url}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$layoutSignoff->getDateAcknowledged()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td colspan="6">
			{translate key="common.file"}:&nbsp;&nbsp;&nbsp;&nbsp;
			{if $layoutFile}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()|escape}</a>&nbsp;&nbsp;{$layoutFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="submission.layout.noLayoutFile"}
			{/if}
		</td>
	</tr>
	{* 
	* 20110825 BLH 	Added if conditional. Was causing fatal error in case where no layout file exists.
	*}
	{if $layoutFile}
	{assign var=layoutFileType value=$layoutFile->getFileType()}
	{/if}
	{if $layoutFile and $layoutFileType != 'application/pdf' and $layoutFileExtension != 'pdf' and (!$useLayoutEditors or ($useLayoutEditors and !$layoutSignoff->getDateCompleted()))}
	<tr valign="top">
		<td colspan="6">
			<form method="post" action="{url op="copyLayoutToGalleyAsPdf"}" enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getId()}" />
				<input type="submit" id="generatePdf" value="{translate key="submission.layout.generatePdf"}" onclick="hideGeneratePdfButton()" class="button" />
				{translate key="submission.layout.generatePdf.info"}<br />
				{translate key="reviewer.article.convertFileToPdf.msword"}
			</form>
		</td>
	</tr>
	{/if}
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>

<tr bgcolor="#FFFF99">
		<td colspan="2">{translate key="submission.layout.galleyFormat"}</td>
		<td colspan="2" class="heading">{translate key="common.file"}</td>
		<td class="heading">{translate key="common.order"}</td>
		<td class="heading">{translate key="common.action"}</td>
		{* BLH we aren't using OJS front-end, so don't display # of views *}
		{* <td class="heading">{translate key="submission.views"}</td> *}
		<td>&nbsp;</td>
	</tr>
	{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr bgcolor="#FFFF99">
		<td width="2%">{$smarty.foreach.galleys.iteration}.</td>
		<td width="26%">{$galley->getGalleyLabel()|escape} &nbsp; <!-- Hiding because we don't support this.  <a href="{url op="proofGalley" path=$submission->getId()|to_array:$galley->getId()}" class="action">{translate key="submission.layout.viewProof"}</a>--></td>
		<td colspan="2"><a href="{url op="downloadFile" path=$submission->getId()|to_array:$galley->getFileId()}" class="file">{$galley->getFileName()|escape}</a>&nbsp;&nbsp;{$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="orderGalley" d=u articleId=$submission->getId() galleyId=$galley->getId()}" class="plain">&uarr;</a> <a href="{url op="orderGalley" d=d articleId=$submission->getId() galleyId=$galley->getId()}" class="plain">&darr;</a></td>
		<td>
			<a href="{url op="editGalley" path=$submission->getId()|to_array:$galley->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteGalley" path=$submission->getId()|to_array:$galley->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.layout.confirmDeleteGalley"}')" class="action">{translate key="common.delete"}</a>
		</td>
		{* BLH we aren't using OJS front-end, so don't display # of views *}
		{* <td>{$galley->getViews()|escape}</td> *}
		<td>&nbsp;</td>
	</tr>
	{foreachelse}
	<tr bgcolor="#FFFF99">
		<td colspan="7" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
        {if $galleyCount > 1}
        <tr>
                <td colspan="3" style="padding: 5px; background: #F0B1A8;">Multiple galley files detected: Please delete extra galley files.</td>
        </tr>
        {/if}
        {if $galleyCount > 0}
        <tr>
                <td colspan="100%"><em>Note: eScholarship supports one galley only. If you would like to replace the existing galley, please click 'EDIT' to replace the file.</em></td>
        </tr>
        {/if}
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>
	<tr bgcolor="#FFFF99">
		<td width="28%" colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td width="34%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td width="16%" class="heading">{translate key="common.order"}</td>
		<td width="16%" colspan="2" class="heading">{translate key="common.action"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr bgcolor="#FFFF99">
		<td width="2%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="26%">{$suppFile->getSuppFileTitle()|escape}</td>
		<td colspan="2"><a href="{url op="downloadFile" path=$submission->getId()|to_array:$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="orderSuppFile" d=u articleId=$submission->getId() suppFileId=$suppFile->getId()}" class="plain">&uarr;</a> <a href="{url op="orderSuppFile" d=d articleId=$submission->getId() suppFileId=$suppFile->getId()}" class="plain">&darr;</a></td>
		<td colspan="2">
			<a href="{url op="editSuppFile" from="submissionEditing" path=$submission->getId()|to_array:$suppFile->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSuppFile" from="submissionEditing" path=$submission->getId()|to_array:$suppFile->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.layout.confirmDeleteSupplementaryFile"}')" class="action">{translate key="common.delete"}</a>
		</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>
</table>

<form name="uploadFile" method="post" action="{url op="uploadLayoutFile"}" enctype="multipart/form-data">
	<input type="hidden" name="from" value="submissionEditing" />
	<input type="hidden" name="articleId" value="{$submission->getId()}" />
	{translate key="submission.uploadFileTo"} 
        <input type="radio" name="layoutFileType" id="layoutFileTypeSubmission" value="submission" checked="checked" /><label for="layoutFileTypeSubmission">{translate key="submission.layout.layoutVersion"}</label>, 
        {if $galleyCount > 0}
            <input type="radio" name="layoutFileType" id="layoutFileTypeGalley" value="galley" disabled="disabled" /><label for="layoutFileTypeGalley" class="disabled">{translate key="submission.galley"}</label>, 
        {else}
            <input type="radio" name="layoutFileType" id="layoutFileTypeGalley" value="galley" /><label for="layoutFileTypeGalley">{translate key="submission.galley"}</label>,
        {/if}
        <input type="radio" name="layoutFileType" id="layoutFileTypeSupp" value="supp" /><label for="layoutFileTypeSupp">{translate key="article.suppFilesAbbrev"}</label>
	<input type="file" name="layoutFile" size="10" class="uploadField" />
	<input type="submit" onclick="return checkForFileToUpload()" value="{translate key="common.upload"}" class="button" />
</form>
<div id="layoutComments">
{translate key="submission.layout.layoutComments"}
{if $submission->getMostRecentLayoutComment()}
	{assign var="comment" value=$submission->getMostRecentLayoutComment()}
	<a href="javascript:openComments('{url op="viewLayoutComments" path=$submission->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{url op="viewLayoutComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
{/if}

{if $currentJournal->getLocalizedSetting('layoutInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{url op="instructions" path="layout"}')" class="action">{translate key="submission.layout.instructions"}</a>
{/if}
</div>
</div>

