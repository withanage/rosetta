<script>
	$(function () {ldelim}
		// Attach the form handler.
		$('#rosettaSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>
<form class="pkp_form" method="post" id="rosettaSettingsForm"
	  action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" plugin="RosettaExportPlugin" category="importexport" verb="settings" save="true"}">

    {fbvFormArea id="rosettaSettingsFormArea"}
		<p class="pkp_help">{translate key="plugins.importexport.rosetta.description"}</p>
    {/fbvFormArea}
    {*
	{fbvFormSection}
	{fbvElement type="text" required="true" id="rosettaHost" value=$rosettaHost label="plugins.importexport.rosetta.rosettaHost" maxlength="50" size=$fbvStyles.size.SMALL}
	{fbvElement type="text" required="true" id="rosettaDepositShare" value=$rosettaDepositShare label="plugins.importexport.rosetta.rosettaDepositShare" maxlength="50" size=$fbvStyles.size.SMALL}
	{fbvElement type="text" required="true"  id="rosettaClientSystemID" value=$rosettaClientSystemID label="plugins.importexport.rosetta.rosettaClientSystemID" maxlength="10" size=$fbvStyles.size.SMALL}
	{/fbvFormSection}
	{fbvFormSection}
		<span class="instruct">{translate key="plugins.importexport.rosetta.rosettaHostInstructions"}</span>
	{fbvElement type="text" required="true" id="rosettaUsername" value=$rosettaUsername label="plugins.importexport.rosetta.rosettaUsername" maxlength="24" size=$fbvStyles.size.SMALL}
	{fbvElement type="text" required="true" password="true" id="rosettaPassword" value=$rosettaPassword label="plugins.importexport.rosetta.rosettaPassword" maxlength="24" size=$fbvStyles.size.SMALL}

	{/fbvFormSection}

	{/fbvFormArea}
	{fbvFormButtons submitText="common.save" hideCancel="true"}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
*}
</form>
